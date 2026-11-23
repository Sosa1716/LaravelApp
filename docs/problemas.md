# Registro de problemas y soluciones

Bitácora de los problemas detectados durante la implementación de DevOps (CI/CD)
y cómo se resolvió cada uno. Formato: **Problema → Impacto → Solución → Fase**.

---

## Fase 0 — Limpieza y seguridad

### P-01 · `APP_KEY` de producción versionado en `render.yaml`
- **Problema:** la clave de cifrado de Laravel estaba escrita en texto plano en `render.yaml`.
- **Impacto:** cualquiera con acceso al repo puede descifrar cookies/sesiones y datos cifrados con `Crypt`, y falsificar URLs firmadas.
- **Solución:** se reemplazó el valor por `sync: false`, así Render pide el valor en el panel y no se versiona.
  La clave antigua **sigue en el historial de git**, por lo que debe considerarse comprometida y **rotarse**:
  1. `php artisan key:generate --show` → copiar el valor `base64:...`.
  2. Render → servicio `el-pinguin-api` → *Environment* → `APP_KEY` = nuevo valor → *Save* (redeploy).
  - Efecto de la rotación: se invalidan las sesiones/cookies cifradas actuales. Los tokens de Sanctum **no** se ven afectados (se guardan como hash SHA-256, no dependen de `APP_KEY`).
  - No se reescribe el historial de git (operación destructiva sobre ramas compartidas); rotar la clave es suficiente.
- **Nota:** no se usa `generateValue: true` de Render porque genera un valor sin el prefijo `base64:` y con un tamaño que no coincide con el cifrado `AES-256-CBC`, lo que rompe Laravel.

### P-02 · `APP_DEBUG=true` en producción
- **Problema:** `render.yaml` activaba el modo debug en producción.
- **Impacto:** ante cualquier error se muestran stack traces, rutas internas y valores de configuración/variables de entorno.
- **Solución:** `APP_DEBUG` = `"false"` en `render.yaml`.

### P-03 · Archivos ajenos a la aplicación versionados
- **Problema:** `.agents/` (skills de IA de animación/Swift), `.codex-build/` (presentaciones `.pptx`, imágenes y scripts) y `skills-lock.json` estaban en git.
- **Impacto:** repo más pesado, ruido en PRs y copia innecesaria (~5 MB) dentro de la imagen Docker.
- **Solución:** `git rm -r --cached` (se dejan de seguir **sin borrarse del disco**) y se añadieron a `.gitignore` y `.dockerignore`.

### P-04 · `render-build.sh` obsoleto
- **Problema:** script de build para el runtime nativo de PHP de Render, pero `render.yaml` usa `runtime: docker`, así que nunca se ejecutaba. Además corría `key:generate --force` (regeneraba la clave en cada deploy) y `db:seed` en cada build.
- **Impacto:** confusión sobre cuál es el proceso real de despliegue.
- **Solución:** se eliminó. El proceso de arranque se rediseña en la Fase 2 (entrypoint del contenedor FrankenPHP).

### P-05 · `.dockerignore` incompleto
- **Problema:** no excluía `.env`, `.github`, `docs`, `tests` ni los directorios de herramientas.
- **Impacto:** riesgo de copiar un `.env` local con secretos a la imagen y tamaño de imagen mayor.
- **Solución:** se añadieron esas rutas (se mantiene `.env.example`).

### P-10 · Código sin formato estándar (Pint)
- **Problema:** `laravel/pint` estaba instalado pero nunca se había ejecutado; `pint --test` fallaba en 32 archivos.
- **Impacto:** el job de *lint* del CI fallaría desde el primer commit.
- **Solución:** se ejecutó `vendor/bin/pint` (preset `laravel`) en un commit aislado de solo formato (`style: ...`), sin cambios de comportamiento. A partir de ahora el CI ejecuta `pint --test`.

### P-11 · Dependencias con vulnerabilidades conocidas
- **Problema:** `composer audit` reportó CVEs en `laravel/framework` 12.69.3 (CVE-2026-128083, corregido en 12.71) y en `symfony/http-foundation` / `symfony/http-kernel` 7.4.20 (Trusted Proxies, generación de URLs con host del cliente, etc.).
- **Impacto:** vulnerabilidades explotables en producción.
- **Solución:** `composer update` dentro de las restricciones de `composer.json` (sin saltos de versión mayor), resuelto con PHP 8.2 para mantener compatibilidad. `laravel/framework` → 12.73.0, `symfony/*` → 7.4.23. `composer audit` queda limpio y se ejecuta en cada pipeline.

### P-12 · Crear órdenes fallaba con 500 en PostgreSQL (bug de producción)
- **Problema:** `OrdenSeeder` inserta órdenes con IDs explícitos (1-4). PostgreSQL no avanza la secuencia `ordenes_id_seq` con inserts de ID explícito, así que el siguiente `Orden::create()` desde la API intenta usar `id=1` → `SQLSTATE[23505] duplicate key value violates unique constraint "ordenes_pkey"`.
- **Cómo se detectó:** al ejecutar la suite contra PostgreSQL 16 (Docker) en lugar de SQLite: `MobileSyncTest` respondía 500 en `POST /api/ordenes`. En SQLite no ocurre, por eso nunca se había visto.
- **Impacto:** en Render (PostgreSQL) los meseros no podían registrar órdenes nuevas después del seed.
- **Solución:** al final de `OrdenSeeder`, si el driver es `pgsql`, se ejecuta `setval(pg_get_serial_sequence('ordenes','id'), MAX(id))`. Lección: el CI debe probar contra el mismo motor que producción (ver matriz de la Fase 1).

### P-13 · Las pruebas fallaban sin `.env` (`MissingAppKeyException`)
- **Problema:** `ExampleTest` (`GET /`) falla con *No application encryption key has been specified* si no existe `.env` con `APP_KEY`.
- **Impacto:** en un runner limpio de CI la suite no pasa.
- **Solución:** el pipeline ejecuta `cp .env.example .env && php artisan key:generate` antes de las pruebas.

---

## Fase 1 — Integración continua (`.github/workflows/deploy.yml`)

### P-08 · Las pruebas solo corrían en SQLite (producción usa PostgreSQL)
- **Problema:** `phpunit.xml` fuerza SQLite en memoria; nunca se probaba contra PostgreSQL.
- **Impacto:** bugs específicos del motor llegaban a producción (ver P-12).
- **Solución:** matriz de CI **SO × PHP × BD** = `ubuntu/windows/macos` × `8.2/8.3` × `sqlite/pgsql` (12 jobs, `fail-fast: false`). Las variables `DB_*` del job tienen prioridad sobre `phpunit.xml` porque `<env>` de PHPUnit no sobrescribe variables ya definidas.

### P-14 · Los *service containers* de GitHub Actions solo existen en Linux
- **Problema:** `services: postgres` no funciona en runners Windows ni macOS, pero la matriz exige PostgreSQL en los tres.
- **Solución:** se usa la acción `ikalnytskyi/action-setup-postgres@v6`, que instala/arranca PostgreSQL nativo en los tres sistemas operativos con el mismo usuario, contraseña y BD.

### P-15 · Diferencias de shell entre sistemas operativos
- **Problema:** en Windows el shell por defecto de los `run:` es PowerShell; `cp`, `mkdir -p` o `$GITHUB_ENV` con `>>` se comportan distinto.
- **Solución:** `defaults.run.shell: bash` en todo el workflow (Git Bash en Windows). Las pruebas se lanzan con `php vendor/bin/phpunit` en lugar de `vendor/bin/phpunit` para no depender del ejecutable `.bat`.

### P-16 · Demasiados checks para proteger ramas
- **Problema:** exigir 12 jobs de matriz + lint como *required checks* es frágil (cualquier cambio de nombre en la matriz rompe la protección).
- **Solución:** job agregador **`CI OK`** (`needs: [lint, tests]`, `if: always()`) que falla si cualquiera falla. Es el único check requerido.

### P-17 · No había pruebas de los endpoints de verificación ni pruebas unitarias reales
- **Solución:** `tests/Feature/HealthTest.php` (`/up`, `/api/version`, login inválido sin 500) y `tests/Unit/RoleMiddlewareTest.php` (401/403/paso permitido). Se añadió `GET /api/version`, que devuelve el commit desplegado (`RENDER_GIT_COMMIT` o `APP_VERSION`) y que el pipeline usa para verificar despliegues.

**Artefactos que sube el pipeline (`actions/upload-artifact`):** `test-results-<so>-php<v>-<bd>` (JUnit de cada combinación), `coverage-report` (Clover + HTML) y `app-release` (tarball con `vendor` de producción + `SHA256SUMS.txt`).

---

## Fase 2 — Docker con FrankenPHP

### P-06 · El contenedor ejecutaba `db:seed --force` en cada arranque
- **Problema:** el `CMD` original corría los seeders cada vez que Render reiniciaba el contenedor (en el plan free, tras cada periodo de inactividad).
- **Impacto:** `OrdenSeeder` **sobrescribe las órdenes 1-4 reales** y sus detalles; `UserSeeder` restablece los usuarios con contraseña `12345678` en producción.
- **Solución:** `docker/entrypoint.sh` solo siembra si `SEED_ON_DEPLOY=true`. Producción usa `false`; staging (BD efímera) usa `true`.

### P-07 · Servidor de desarrollo y permisos 777 en producción
- **Problema:** se usaba `php artisan serve` (servidor embebido de PHP, un solo proceso, no apto para producción) y `chmod -R 777` como root.
- **Solución:** imagen `dunglas/frankenphp:1-php8.3-bookworm` (Caddy + PHP 8.3) con `frankenphp php-server`, `php.ini-production` + OPcache sin validación de timestamps, y **usuario `app` (uid 10001) sin privilegios** que solo puede escribir en `storage`, `bootstrap/cache` y `database`.
- **Verificación:** `docker exec ... id` → `uid=10001(app)`; `HEALTHCHECK` contra `/up` → `healthy`.

### P-18 · `view:cache` fallaba en el build anterior
- **Problema (histórico, commit `b1fe849`):** `.dockerignore` excluye `storage/framework/*`, así que en la imagen no existían `storage/framework/views` ni `cache`, y `view:cache` fallaba.
- **Solución:** el `Dockerfile` crea los directorios de runtime y las cachés (`config`, `route`, `view`, `event`) se generan en el **entrypoint**, no en el build, para que lean las variables reales del entorno (Render las inyecta al arrancar).

### P-19 · Imagen de una sola etapa con Composer y herramientas de build
- **Solución:** build **multi-stage**: la etapa `vendor` (`composer:2`) instala dependencias sin dev; la etapa final solo copia `vendor/`, genera el autoloader optimizado (`--classmap-authoritative`) y **elimina el binario de Composer**.

### P-20 · El puerto lo decide la plataforma en tiempo de ejecución
- **Problema:** Render inyecta `PORT` al arrancar; un `CMD` en forma exec no expande variables.
- **Solución:** el entrypoint hace `exec frankenphp php-server --listen ":${PORT}"` (por defecto 8080). El smoke test del CI arranca la imagen con `PORT=10000` para simular Render.

### P-21 · Riesgo de copiar una base SQLite local a la imagen
- **Solución:** `database/*.sqlite*` y `docker-compose.yml` añadidos a `.dockerignore`.

### P-22 · Contenedor arrancaba sin `APP_KEY` y fallaba en la primera petición
- **Solución:** el entrypoint valida `APP_KEY` y termina con un mensaje claro si falta. Las migraciones se reintentan hasta 10 veces (la BD gestionada puede tardar en aceptar conexiones).

**Verificación local realizada:** `docker compose up` (FrankenPHP + PostgreSQL 16) → reinicio del contenedor con re-seed → `POST /api/ordenes` = **201** (P-12 corregido también en la imagen) y sin duplicar detalles.

**En el pipeline:** job `docker` → build con caché de GitHub Actions, smoke test del contenedor (`/up`, `/api/version` = SHA del commit, login 200, usuario no-root), push a **GHCR** (`sha-<commit>`, `<rama>`, `latest` en `main`) con SBOM y provenance, y artefacto `docker-build` (`build-info.json`, tags, reporte del smoke test).

---

## Fase 3 — Despliegue continuo (Render) y release

### P-09 · No existía entorno de staging
- **Solución:** segundo servicio `el-pinguin-api-staging` en `render.yaml`, que sigue la rama `staging`, con el mismo `Dockerfile` que producción.

### P-23 · El plan free de Render permite una sola base PostgreSQL
- **Problema:** crear `el-pinguin-db-staging` falla en el plan gratuito (límite de una BD PostgreSQL free por cuenta).
- **Solución:** staging usa **SQLite dentro del contenedor**, recreado y sembrado (`SEED_ON_DEPLOY=true`) en cada deploy. Ventaja: datos conocidos para la verificación automática (login real con el admin sembrado). La compatibilidad con PostgreSQL la cubre la matriz de CI (P-08) y producción sigue en PostgreSQL. Si en el futuro se usa un plan de pago, basta con cambiar las `envVars` de staging a una BD propia.

### P-24 · Render desplegaba en cada push aunque las pruebas fallaran
- **Problema:** con auto-deploy, Render construye cualquier commit de la rama, sin esperar al CI.
- **Solución:** `autoDeploy: false` en ambos servicios. El job `deploy` llama al **Deploy Hook** de Render solo después de `ci-ok`, `package` y `docker`. Además, `healthCheckPath: /up`, así Render no cambia el tráfico a una versión que no arranca.

### P-25 · El Deploy Hook es asíncrono: "200 OK" no significa "desplegado"
- **Problema:** el hook solo encola el deploy; el pipeline no sabría si la nueva versión quedó en línea ni cuándo.
- **Solución:** el job consulta `GET /api/version` cada 20 s (máximo 20 min, para cubrir el build y el arranque en frío del plan free) hasta que devuelva **el SHA exacto del commit** (`RENDER_GIT_COMMIT`). Después corre un smoke test: `/up` 200, login inválido 401/422 (demuestra que la BD responde), rutas protegidas 401 sin token y, en staging, login real + `GET /api/ordenes` 200. El reporte se sube como artefacto `deploy-report-<entorno>`.

### P-26 · Release solo después de verificar staging
- **Requisito:** el tag `v1.0.0` y el GitHub Release se crean **solo si staging pasa la verificación**.
- **Solución:** job `release` con `needs: [deploy, docker]` que solo corre en pushes a `staging`. Es **idempotente**: si el tag ya existe, no hace nada (los siguientes pushes a staging no fallan). Adjunta al release el tarball de la app + `SHA256SUMS.txt`, `build-info.json`, el reporte de verificación de staging, cobertura y resultados de pruebas, y etiqueta en GHCR **la misma imagen (mismo digest)** verificada como `:v1.0.0`. Las notas salen de la sección `## [1.0.0]` de `CHANGELOG.md`. La versión se puede cambiar con la variable de repositorio `RELEASE_TAG`.

### P-27 · Secretos de despliegue por entorno
- **Solución:** GitHub **Environments** `staging` y `production`, cada uno con su secret `RENDER_DEPLOY_HOOK_URL` y su variable `APP_URL`. `production` exige **aprobación manual** (required reviewers). Si falta configuración, el job falla con un mensaje explícito en lugar de "pasar" en silencio.

---

## Fase 4 — Gobernanza del repositorio

### P-28 · Cualquiera podía hacer push directo a `main`/`staging`
- **Problema:** sin reglas de protección, un push directo salta el CI y (con el CD) desplegaría sin revisión.
- **Solución:** protección de ramas `main`, `staging` y `develop`: PR obligatorio, check requerido **`CI OK`** (ver P-16), rama actualizada antes de fusionar, sin force-push ni borrado. En `main` y `staging` además se exige 1 aprobación y que las conversaciones estén resueltas.

### P-29 · Dependencias sin mantenimiento automático
- **Problema:** las vulnerabilidades de P-11 se acumularon porque nadie actualizaba dependencias.
- **Solución:** `.github/dependabot.yml` semanal para **composer**, **npm**, **github-actions** y **docker** (imagen base de FrankenPHP). Los PRs apuntan a `develop` y se agrupan (laravel/*, symfony/*, dev) para no saturar el repo.

### P-30 · PRs e issues sin estructura
- **Solución:** plantilla de PR con checklist (Pint, pruebas, migraciones en SQLite **y** PostgreSQL, variables de entorno, bitácora, changelog), formularios de issue (bug y funcionalidad, con el entorno y la versión de `/api/version`) y `CODEOWNERS` para la revisión obligatoria de la infraestructura (`.github/`, `Dockerfile`, `docker/`, `render.yaml`).

---

## Fase 5 — Documentación

### P-31 · README genérico de Laravel
- **Problema:** el `README.md` era el del esqueleto de Laravel; no explicaba el proyecto, cómo correrlo, el flujo de ramas ni el pipeline.
- **Solución:** README del proyecto con stack, estructura, puesta en marcha (Docker y PHP local), pruebas contra PostgreSQL, flujo de ramas, diagrama del pipeline (Mermaid), tabla de jobs y artefactos, configuración de Render y GitHub Environments, y referencia de la API. Se añadió `CHANGELOG.md` (fuente de las notas del release).

---

## Puesta en marcha en GitHub

### P-32 · Coste de minutos de la matriz multiplataforma en un repo privado
- **Problema:** el repositorio es privado. En el plan gratuito de GitHub hay 2 000 minutos/mes y los runners **Windows cuentan ×2** y **macOS ×10**. La matriz completa (12 jobs) consume ≈ 270 minutos facturables por ejecución; correrla en cada PR y en cada push agotaría la cuota en pocos días.
- **Solución:** matriz dinámica con `fromJSON`: en **pull requests** solo `ubuntu-latest` (4 jobs: PHP 8.2/8.3 × SQLite/PostgreSQL); en **pushes a `develop`, `staging` y `main`** y en ejecución manual (`workflow_dispatch`) la matriz completa Ubuntu/Windows/macOS. Así cada cambio que entra a una rama de integración o despliegue se valida en los tres SO, y los PRs dan feedback rápido y barato.
- **Además:** las fases se integraron con PRs apilados (cada fase con su PR) para ejecutar el CI completo una sola vez al llegar a `develop`.

### P-33 · Permisos insuficientes para configurar el repositorio
- **Problema:** la cuenta usada para la automatización tiene permiso **Write**, no **Admin**, sobre `Sosa1716/LaravelApp`. La protección de ramas, los Environments y sus secrets requieren Admin (la API responde 404).
- **Solución:** esa configuración la aplica el propietario del repositorio siguiendo la sección *Entornos y configuración* del README. Todo lo demás (código, pipeline, PRs, merges) se automatizó.

### P-34 · Acciones con runtime Node.js 20 (obsoleto)
- **Problema:** la primera ejecución en GitHub (PR #1) pasó, pero con avisos: *"Node.js 20 is deprecated... actions/cache@v4, actions/upload-artifact@v4, docker/*"*. Cuando GitHub retire Node 20 esas acciones dejarán de funcionar. Además, los jobs `deploy`/`release` omitidos mostraban su nombre sin evaluar (`${{ ... }}`).
- **Solución:** se actualizó cada acción a la **versión mayor más baja que ya usa Node 24**, revisando sus notas de release (solo cambian el runtime o eliminan entradas que este workflow no usa): `cache@v5`, `upload-artifact@v6`, `download-artifact@v7`, `setup-buildx-action@v4`, `build-push-action@v7`, `metadata-action@v6`, `login-action@v4`. Los jobs de deploy y release tienen ahora nombres fijos; el entorno se ve en el Environment del job.
