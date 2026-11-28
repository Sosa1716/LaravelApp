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
- **Solución:** se usa la acción `ikalnytskyi/action-setup-postgres` (v9, ver P-35), que instala/arranca PostgreSQL nativo en los tres sistemas operativos con el mismo usuario, contraseña y BD.

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
- **Solución:** matriz dinámica con `fromJSON`. Primera versión: solo Ubuntu en PRs y matriz completa en pushes a `develop`, `staging` y `main`. Tras varias ejecuciones completas se ajustó (ver P-37): **matriz completa Ubuntu/Windows/macOS solo en pushes a `staging` y `main`** (lo que se despliega) y en ejecución manual (`workflow_dispatch`); **PRs y `develop` solo `ubuntu-latest`** (4 jobs: PHP 8.2/8.3 × SQLite/PostgreSQL). Todo lo que llega a un entorno desplegado queda validado en los tres SO, y la integración diaria es rápida y barata.
- **Además:** las fases se integraron con PRs apilados (cada fase con su PR) para ejecutar el CI completo una sola vez al llegar a `develop`.

### P-33 · Permisos insuficientes para configurar el repositorio
- **Problema:** la cuenta usada para la automatización tiene permiso **Write**, no **Admin**, sobre `Sosa1716/LaravelApp`. La protección de ramas, los Environments y sus secrets requieren Admin (la API responde 404).
- **Solución:** esa configuración la aplica el propietario del repositorio siguiendo la sección *Entornos y configuración* del README. Todo lo demás (código, pipeline, PRs, merges) se automatizó.

### P-34 · Acciones con runtime Node.js 20 (obsoleto)
- **Problema:** la primera ejecución en GitHub (PR #1) pasó, pero con avisos: *"Node.js 20 is deprecated... actions/cache@v4, actions/upload-artifact@v4, docker/*"*. Cuando GitHub retire Node 20 esas acciones dejarán de funcionar. Además, los jobs `deploy`/`release` omitidos mostraban su nombre sin evaluar (`${{ ... }}`).
- **Solución:** se actualizó cada acción a la **versión mayor más baja que ya usa Node 24**, revisando sus notas de release (solo cambian el runtime o eliminan entradas que este workflow no usa): `cache@v5`, `upload-artifact@v6`, `download-artifact@v7`, `setup-buildx-action@v4`, `build-push-action@v7`, `metadata-action@v6`, `login-action@v4`. Los jobs de deploy y release tienen ahora nombres fijos; el entorno se ve en el Environment del job.

### P-35 · PostgreSQL no arrancaba en los runners macOS
- **Problema:** en la primera ejecución de la matriz completa (push a `develop`) pasaron los 4 jobs de Windows y los 4 de Ubuntu, pero `macos-latest` + `pgsql` falló en "Levantar PostgreSQL" con *exit code 127* (comando no encontrado).
- **Causa:** `ikalnytskyi/action-setup-postgres@v6` solo instala PostgreSQL con Homebrew en macOS 13/14; la imagen actual de `macos-latest` es más nueva y ya no trae PostgreSQL preinstalado, así que `initdb` no existía.
- **Solución:** actualizar a `@v9`, que instala PostgreSQL con Homebrew en cualquier versión de macOS, y fijar `postgres-version: "16"` para usar en CI la misma versión mayor que `docker-compose.yml`. Los parámetros `username/password/database/port` no cambiaron.
- **Lección:** `macos-latest` cambia de versión sin aviso; por eso la matriz completa corre en cada push a las ramas de integración.

---

## Primer despliegue a staging

### P-36 · El contenedor de staging se detenía con *exit status 1*
- **Síntoma:** primer push a `staging` (PR #8): CI 12/12 en verde y Render construyó la imagen, pero el deploy falló con *"Exited with status 1 while running your code"*. El job `deploy` esperó 20 minutos a que `/api/version` sirviera el commit, terminó por timeout y **el release no se creó** (comportamiento esperado: sin verificación no hay versión).
- **Diagnóstico:** el log de Render mostraba el mensaje del entrypoint: `[entrypoint] ERROR: APP_KEY no está definida`. Al crear el servicio de staging no se cargó la variable `APP_KEY`.
- **Solución:** se generó una clave con `php artisan key:generate --show` y se cargó en *Environment* del servicio de staging. Se relanzó **solo el job de deploy** (`gh run rerun --failed`), sin repetir las pruebas.
- **Lección:** la validación de `APP_KEY` en el entrypoint (P-22) cumplió su función: el contenedor se negó a arrancar con un error claro en lugar de quedar en línea y fallar en cada petición, y Render conservó la versión anterior.

### P-37 · Render: `exec: frankenphp: Operation not permitted` (*exit status 126*)
- **Síntoma:** tras cargar `APP_KEY`, el siguiente deploy avanzó más (migraciones, seed, cachés) y falló con *"Exited with status 126"* justo al lanzar el servidor.
- **Causa:** la imagen oficial de FrankenPHP marca el binario con la *file capability* `cap_net_bind_service=ep` (`getcap /usr/local/bin/frankenphp`) para poder usar puertos < 1024 sin root. Render ejecuta los contenedores sin esa capacidad; cuando un usuario sin privilegios (`app`, uid 10001) ejecuta un binario que la exige, el kernel lo rechaza con `EPERM`. En Docker Desktop y en el smoke test del CI funcionaba porque Docker concede esa capacidad por defecto.
- **Reproducción local:** `docker run --cap-drop=ALL ...` → `/usr/local/bin/entrypoint: 57: exec: frankenphp: Operation not permitted`, exit 126 (idéntico a Render).
- **Solución:** `setcap -r /usr/local/bin/frankenphp` en el `Dockerfile`. No se necesita la capacidad porque el servidor escucha en `$PORT` (8080/10000). Verificado con `--cap-drop=ALL`: `/up` 200, login 200, proceso como `uid=10001`.
- **Prevención:** el smoke test del job `docker` ahora arranca el contenedor con `--cap-drop=ALL`. Se comprobó que con esa opción la imagen anterior falla (exit 126), así que el CI detecta este tipo de error antes de llegar a Render.

### P-38 · Staging arrancaba sin datos de prueba (login del admin fallaba)
- **Síntoma:** con el exit 126 corregido, Render ya servía el commit nuevo y pasaban `/up` (200), login inválido (422) y ruta protegida sin token (401), pero falló el chequeo **login con el admin sembrado** (esperado 200). El release no se creó.
- **Diagnóstico:** `/api/version` respondía `"app":"Laravel","env":"production"` en vez de `"El Pinguin (staging)"`/`"staging"`. Al servicio de staging solo se le había cargado `APP_KEY`; faltaban `SEED_ON_DEPLOY=true` y el resto de variables, así que no se ejecutaban los seeders.
- **Solución:** se cargaron en Render las variables de staging definidas en `render.yaml` (`APP_ENV=staging`, `SEED_ON_DEPLOY=true`, `DB_CONNECTION=sqlite`, etc.).
- **Lección:** el smoke test con login real detectó un **ambiente mal configurado** que los chequeos básicos (`/up`) no habrían detectado. Exponer `app` y `env` en `/api/version` hizo el diagnóstico inmediato.

### P-39 · La verificación no distinguía un redeploy del mismo commit
- **Síntoma:** al relanzar el deploy tras corregir P-38, el pipeline mostró *"Render ya sirve 2762670"* **en el mismo segundo** en que llamó al hook y verificó la instancia **anterior** (todavía sin datos). Unos 30 s después, la instancia nueva ya respondía login 200.
- **Causa:** la espera comparaba solo el SHA de `/api/version`. Al redesplegar el mismo commit (cambio de variables, reintento), la instancia vieja ya devolvía ese SHA, así que el pipeline daba el deploy por terminado antes de tiempo. La verificación podía aprobar (o rechazar) una instancia que no era la desplegada.
- **Solución:** `docker/entrypoint.sh` exporta `APP_BOOTED_AT` (momento de arranque de esa instancia) y `/api/version` lo expone como `booted_at`. El job `deploy` lee `booted_at` **antes** de llamar al hook y espera a que `/api/version` devuelva **el SHA del commit y un `booted_at` distinto**. Así se verifica siempre la instancia recién desplegada.
- **Verificación local:** dos contenedores de la misma imagen reportan `booted_at` distintos (`04:15:03Z` y `04:15:05Z`).

### P-40 · El release falló al descargar artefactos (`*.dockerbuild`)
- **Síntoma:** pipeline de staging con todo en verde, incluida la verificación post-deploy de la instancia nueva (5/5 chequeos), pero el job `release` falló en "Descargar artefactos del pipeline": *"Unable to download and extract artifact: Artifact download failed after 5 retries"*.
- **Causa:** `docker/build-push-action@v7` sube automáticamente **registros de build** como artefactos (`<owner>~<repo>~<id>.dockerbuild`) que no son ZIP estándar. El paso descargaba *todos* los artefactos sin filtro y falló al intentar extraer esos registros.
- **Solución:** `download-artifact` con `pattern: '{app-release,docker-build,deploy-report-staging,coverage-report,test-results-*}'`, es decir, solo lo que se adjunta al release. Patrón comprobado con minimatch: incluye los 5 tipos y excluye los `.dockerbuild`.
- **Nota:** relanzar solo el job no basta, porque un *re-run* usa el workflow del commit original. La corrección se integró por el flujo normal (`develop` → `staging`).

### P-41 · Windows + PostgreSQL falló por caída de Chocolatey (servicio externo)
- **Síntoma:** en el pipeline de staging (run 45328480846) pasaron 10 de 12 combinaciones; fallaron solo `windows-latest` + `pgsql` (PHP 8.2 y 8.3) en "Levantar PostgreSQL" con *exit code 127*. En ejecuciones anteriores, con la misma configuración, Windows había pasado.
- **Causa:** `action-setup-postgres@v9` instala PostgreSQL en Windows con **Chocolatey**, que lo descarga de internet. El repositorio de paquetes respondió `504 Gateway Timeout` (`Unable to find package 'postgresql16'`), Chocolatey reportó "0/0 paquetes" sin error y el paso siguiente no encontró `pg_config.exe`. Al comprobarlo desde fuera, `community.chocolatey.org` respondía `503` o no respondía: **caída del servicio externo**, no un error del proyecto.
- **Solución:** quitar la dependencia de la red en Windows. En ese SO se usa `action-setup-postgres@v6`, que arranca el **PostgreSQL preinstalado en la imagen del runner** (con esa versión Windows ya había pasado en la primera ejecución completa). Linux y macOS siguen con `@v9` y PostgreSQL 16 (en macOS v9 es imprescindible, ver P-35).
- **Trade-off:** en Windows la versión mayor de PostgreSQL es la que trae el runner (puede no ser 16). La paridad exacta con producción la garantizan Linux (CI) y la imagen Docker; Windows valida la compatibilidad multiplataforma.
- **Lección:** un pipeline no debería depender de servicios externos frágiles en el camino crítico si existe una alternativa local.

---

## Pruebas E2E con Selenium

### P-42 · Ningún clic llegaba a la página después de iniciar sesión como Cocina
- **Síntoma:** en la primera ejecución de las pruebas Selenium pasaban las de login y Administración, pero en la vista de Cocina ningún clic tenía efecto (filtros, "Salir"). El navegador no recibía **ningún** evento (`pointerdown`, `click`), ni con clic nativo, ni con `ActionChains`, ni enviando el clic directo por el protocolo de Chrome (CDP); en cambio `element.click()` por JavaScript sí funcionaba. Pasaba igual en modo headless y con ventana visible.
- **Diagnóstico (por descarte):** se descartaron capas superpuestas (`elementsFromPoint` devolvía el botón), estilos (`pointer-events`, `inert`, opacidad), *polling*, animaciones, `prefers-reduced-motion`, `backdrop-filter` y avisos *toast*. La pista decisiva: tras el login de Cocina `document.hasFocus()` era `false`, y con Administración `true`.
- **Causa:** el **gestor de contraseñas de Chrome** detecta que `12345678` (contraseña de los usuarios de demostración) aparece en filtraciones públicas y abre su aviso de "contraseña comprometida". Ese aviso del navegador le quita el foco a la página y la entrada de mouse deja de llegar al documento. No es un error de la aplicación.
- **Solución:** el Chrome de las pruebas arranca con el gestor de contraseñas y la detección de filtraciones desactivados (`credentials_enable_service`, `profile.password_manager_leak_detection`, `--disable-features=PasswordLeakDetection`). Verificado: la página conserva el foco y los clics llegan.
- **Lección:** en pruebas E2E, el navegador también es parte del sistema bajo prueba; sus diálogos propios pueden interferir. Y conviene no usar contraseñas débiles ni siquiera en datos de demostración.

### P-43 · "Element click intercepted" por la cabecera fija
- **Síntoma:** la prueba de la orden móvil completaba todo el flujo, pero fallaba en el último clic (filtro "Listas"): *"Other element would receive the click"*.
- **Causa:** tras desplazar la página hasta la tarjeta de la orden, la cabecera `sticky` de Cocina quedaba encima de los filtros.
- **Solución:** volver al inicio (`window.scrollTo(0, 0)`) antes de usar los filtros. Selenium reportó bien el problema: un usuario tampoco podría pulsar un botón tapado.

### P-44 · Pruebas que modifican datos y deben ser repetibles
- **Problema:** la prueba de Cocina necesita una orden *pendiente*; con los datos sembrados solo hay una, así que una segunda ejecución fallaría.
- **Solución:** la prueba crea su propia orden por la API como lo haría la app móvil del mesero, y luego la sigue en la interfaz. Así es repetible (verificado con dos ejecuciones seguidas, 7/7) y además prueba la sincronización app móvil → web. Las pruebas E2E solo corren contra staging, cuya base se recrea en cada deploy, nunca contra producción.

### P-45 · Commits directos a `main` saltándose el flujo (y CVEs nuevos que bloqueaban el pipeline)
- **Síntoma:** al integrar las pruebas E2E, el CI falló en `composer audit`. Al revisar se encontraron además 6 commits subidos **directo a `main`** (24-27 nov: CRUD de usuarios, notificaciones, estado por platillo en cocina, entrega por mesa), sin pasar por `develop` ni `staging`. Sus ejecuciones del pipeline también fallaron en `composer audit` y **no se desplegaron** (el job `deploy` depende de `ci-ok`): producción quedó protegida.
- **Causa:** (1) se publicaron CVE nuevos de Symfony (CVE-2026-133743, -133746, -133751, -133755); (2) `main` no tenía protección de rama (requiere rol Admin, P-28/P-33).
- **Solución:** `main` se integró en `develop` (fusión sin conflictos), se actualizó `symfony/*` a 7.4.24/7.4.25 (`composer audit` limpio) y se verificó el código nuevo: Pint OK y **27 pruebas** en SQLite y PostgreSQL. Las pruebas E2E se adaptaron a la nueva interfaz de cocina (tarjetas por mesa y estado por platillo): ahora la prueba 4 prepara el platillo concreto de la comanda y verifica el cambio también en la API.
- **Lección:** el pipeline funcionó como red de seguridad (bloqueó el despliegue de código no auditado), pero la protección de ramas es necesaria para que el flujo `develop → staging → main` no dependa de la disciplina de cada integrante.
