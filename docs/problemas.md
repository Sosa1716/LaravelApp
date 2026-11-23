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

## Pendientes detectados (se resuelven en fases posteriores)

| ID | Problema | Fase |
|----|----------|------|
| P-06 | `Dockerfile` ejecuta `db:seed --force` en **cada arranque** (duplica datos / crea usuarios con contraseña `12345678` en producción). | 2 |
| P-07 | Se usa `php artisan serve` (servidor de desarrollo) y `chmod 777` en la imagen. | 2 |
| P-09 | No existe entorno de staging en Render. | 3 |
