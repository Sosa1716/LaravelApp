# Entrega: El Pinguin API con flujo DevOps (CI/CD)

Este documento relaciona cada criterio de evaluación con su evidencia: archivo del repositorio,
ejecución de GitHub Actions, URL o artefacto.

- **Repositorio:** https://github.com/Sosa1716/LaravelApp
- **Pipeline:** [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml)
- **Ejecuciones:** https://github.com/Sosa1716/LaravelApp/actions/workflows/deploy.yml
- **Versión validada:** [Release v1.0.0](https://github.com/Sosa1716/LaravelApp/releases/tag/v1.0.0)

| Ambiente | URL | Rama | Base de datos |
|---|---|---|---|
| Pruebas (staging) | https://el-pinguin-api-staging.onrender.com | `staging` | SQLite, sembrada en cada deploy |
| Producción | https://el-pinguin-api.onrender.com | `main` | PostgreSQL gestionado |

Comprobación rápida de cada ambiente: `GET /up` (salud) y `GET /api/version`, que devuelve el
commit desplegado y la hora de arranque de la instancia.

---

## Ejecuciones clave

| Ejecución | Qué demuestra | Resultado |
|---|---|---|
| [45329866116](https://github.com/Sosa1716/LaravelApp/actions/runs/45329866116) (push a `staging`) | CI multiplataforma 12/12, imagen en GHCR, **deploy a staging verificado** y **release v1.0.0** | ✅ 18/18 jobs |
| [45331103311](https://github.com/Sosa1716/LaravelApp/actions/runs/45331103311) (push a `main`) | CI 12/12, imagen `latest`, **deploy a producción verificado** | ✅ 17/17 (release omitido a propósito) |
| [45275859946](https://github.com/Sosa1716/LaravelApp/actions/runs/45275859946) | Primer fallo real detectado por la matriz: PostgreSQL en macOS (P-35) | ❌ → corregido |
| [45287966814](https://github.com/Sosa1716/LaravelApp/actions/runs/45287966814) | El deploy a staging no se aprueba si el contenedor no arranca (falta `APP_KEY`, P-36); **no se crea release** | ❌ → corregido |
| [45322732478](https://github.com/Sosa1716/LaravelApp/actions/runs/45322732478) | La verificación detecta un staging mal configurado (sin datos de prueba, P-38) | ❌ → corregido |
| [45328480846](https://github.com/Sosa1716/LaravelApp/actions/runs/45328480846) | Caída de un servicio externo (Chocolatey) detectada en Windows (P-41) | ❌ → corregido |

Las ejecuciones fallidas también son evidencia: muestran que el pipeline **bloquea** la liberación
cuando algo no funciona.

---

## Criterios de evaluación

### 1. Código fuente del sistema web multiplataforma
- API REST en Laravel 12 (PHP 8.2/8.3): [`app/`](../app), [`routes/api.php`](../routes/api.php).
- **Multiplataforma**, en dos sentidos:
  - Se ejecuta y prueba en **Linux, Windows y macOS** (criterio 15).
  - Se distribuye como **imagen Docker** que corre en cualquier sistema con Docker: [`Dockerfile`](../Dockerfile), [`docker-compose.yml`](../docker-compose.yml).
- Clientes: app móvil (mesero) y web (cocina/administración) contra la misma API (`tests/Feature/MobileSyncTest.php`).

### 2. Organización de carpetas y archivos
- Estructura documentada en el [README, sección *Estructura del proyecto*](../README.md#estructura-del-proyecto).
- Separación por responsabilidad: `app/` (código), `database/` (migraciones y seeders),
  `tests/Feature` y `tests/Unit` (pruebas), `docker/` (runtime), `.github/` (CI/CD y gobernanza),
  `docs/` (documentación de la entrega).

### 3. Configuración de ramas de trabajo
- Ramas: `main` (producción), `staging` (pruebas), `develop` (integración) y ramas de trabajo
  `feat/*`, `fix/*`, `chore/*` y `docs/*`.
- Flujo: `feature → PR → develop → PR → staging → PR → main`. Todo cambio entró por Pull Request
  (PRs #1 a #17), con el CI corriendo en cada uno.
- Detalle: [README, sección *Flujo de ramas*](../README.md#flujo-de-ramas).

### 4. Archivo `.github/workflows/deploy.yml`
- [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml): **un único workflow** con todo el flujo.
  Jobs: `lint` → `tests` (matriz) → `ci-ok` → `package` + `docker` → `deploy` → `release`.

### 5. Ejecución automática del pipeline
- Disparadores: `push` a `develop`/`staging`/`main`, `pull_request` hacia esas ramas y `workflow_dispatch`.
- Historial de ejecuciones automáticas: https://github.com/Sosa1716/LaravelApp/actions/workflows/deploy.yml

### 6. Evidencias de integración, construcción y despliegue
| Proceso | Job | Evidencia |
|---|---|---|
| Integración | `lint`, `tests`, `ci-ok` | Pint, `composer audit` y 18 pruebas en 12 combinaciones (run 45329866116) |
| Construcción | `package`, `docker` | Tarball de la app + imagen Docker con smoke test, publicada en [GHCR](https://github.com/users/Sosa1716/packages/container/package/laravelapp) |
| Despliegue | `deploy` | Deploy en Render vía Deploy Hook + verificación de la instancia nueva (runs 45329866116 y 45331103311) |

### 7. Entrega automatizada de los artefactos
Cada ejecución sube con `actions/upload-artifact`:

| Artefacto | Contenido |
|---|---|
| `test-results-<so>-php<v>-<bd>` | Reporte JUnit de cada combinación de la matriz |
| `coverage-report` | Cobertura de código (Clover + HTML) |
| `app-release` | `el-pinguin-api-<sha>.tar.gz` + `SHA256SUMS.txt` |
| `docker-build` | `build-info.json` (imagen, digest, commit) y reporte del smoke test del contenedor |
| `deploy-report-<entorno>` | Resultado de la verificación post-deploy |

Además, la **imagen Docker** se publica en GHCR (`sha-<commit>`, `develop`, `staging`, `main`, `latest`, `v1.0.0`)
y el **release v1.0.0** adjunta automáticamente los artefactos.

### 8. URL o evidencia del ambiente de pruebas
- **https://el-pinguin-api-staging.onrender.com** (`/up`, `/api/version`).
- GitHub Environment `staging` con su historial de deploys: https://github.com/Sosa1716/LaravelApp/deployments
- Reporte de verificación: `smoke-staging.txt` dentro del [release v1.0.0](https://github.com/Sosa1716/LaravelApp/releases/tag/v1.0.0).

### 9. Resultados de pruebas funcionales
- **18 pruebas / 121 aserciones** (PHPUnit 11):
  - `AdminTest`: CRUD de categorías, platillos y mesas; consulta de órdenes y pagos.
  - `CocinaTest`: login con rol Cocina, ver órdenes, cambiar estado (preparando → listo), permisos denegados (403).
  - `MobileSyncTest`: orden creada desde la app móvil aparece en cocina y administración.
  - `HealthTest`: `/up`, `/api/version`, login inválido sin error 500.
  - `RoleMiddlewareTest` (unitaria): 401 / 403 / acceso permitido.
- Resultados: artefactos `test-results-*` y `coverage-report`, y `test-results.zip` en el release.
- **Pruebas funcionales sobre el ambiente desplegado** (smoke test post-deploy en staging):
  ```
  OK  GET /up                                   -> 200
  OK  POST /api/login (credenciales inválidas)  -> 422
  OK  GET /api/ordenes sin token                -> 401
  OK  POST /api/login (admin sembrado)          -> 200
  OK  GET /api/ordenes con token                -> 200
  ```

### 10. Entrega de una versión validada del sistema
- **[Release v1.0.0](https://github.com/Sosa1716/LaravelApp/releases/tag/v1.0.0)**: lo crea automáticamente el job `release`
  **solo si staging pasa la verificación**. Incluye la app empaquetada, checksums, resultados de pruebas,
  cobertura, datos de la imagen y el reporte de verificación de staging.
- Imagen Docker validada: `ghcr.io/sosa1716/laravelapp:v1.0.0` (mismo digest que se verificó en staging).
- Notas de la versión: [`CHANGELOG.md`](../CHANGELOG.md).

### 11. Evidencia de despliegue exitoso
- Staging: run [45329866116](https://github.com/Sosa1716/LaravelApp/actions/runs/45329866116), job *Deploy (Render)* → *"Render ya sirve 2be6b96 en una instancia nueva"* + 5/5 chequeos.
- Producción: run [45331103311](https://github.com/Sosa1716/LaravelApp/actions/runs/45331103311), job *Deploy (Render)* → *"Render ya sirve 61794e2 en una instancia nueva"* + 3/3 chequeos.
- En vivo: `https://el-pinguin-api.onrender.com/api/version` devuelve el commit de producción.

### 12. Verificación del funcionamiento antes de una posible liberación a producción
- El job `deploy` en staging **espera a la instancia nueva** (mismo SHA y `booted_at` distinto, P-39) y ejecuta un
  smoke test con **login real y consulta de órdenes**.
- El job `release` depende de esa verificación: sin staging verificado **no hay versión** (demostrado en los runs fallidos de la tabla).
- Solo después se promovió `staging → main` (PR #17), y producción se verificó de nuevo tras el deploy.

### 13. Descripción del proceso de CD implementado
1. Un PR fusionado en `staging` o `main` dispara el pipeline completo.
2. Tras pasar el CI (`ci-ok`), se construye la imagen Docker, se prueba el contenedor con las mismas
   restricciones de Render (`--cap-drop=ALL`, `PORT=10000`) y se publica en GHCR.
3. El job `deploy` (GitHub Environment `staging` o `production`) guarda el `booted_at` actual y llama al
   **Deploy Hook** de Render. Render tiene el auto-deploy desactivado: solo despliega el pipeline.
4. Render construye y arranca la nueva versión. El contenedor migra la BD, siembra solo en staging, cachea
   la configuración y sirve con FrankenPHP. Render solo cambia el tráfico cuando `/up` responde.
5. El pipeline espera a que `/api/version` muestre el commit **y** una instancia nueva, y ejecuta el smoke test.
6. En staging, si todo pasa, se crea el tag y el **GitHub Release** y la imagen se etiqueta como `v1.0.0`.
7. La promoción a producción es un PR `staging → main`, con el mismo despliegue y verificación.

Detalle técnico: [README, sección *Pipeline de CI/CD*](../README.md#pipeline-de-cicd) y [`docs/problemas.md`](problemas.md) (Fase 3).

### 14. Capturas de pantalla de GitHub Actions
Ver la sección [Capturas a incluir](#capturas-a-incluir) al final.

### 15. Evidencias de funcionamiento multiplataforma
Matriz del job `tests` en los runs 45329866116 (staging) y 45331103311 (producción), **12/12 en verde**:

| | SQLite | PostgreSQL |
|---|---|---|
| **Ubuntu** | ✅ PHP 8.2 · ✅ PHP 8.3 | ✅ PHP 8.2 · ✅ PHP 8.3 |
| **Windows** | ✅ PHP 8.2 · ✅ PHP 8.3 | ✅ PHP 8.2 · ✅ PHP 8.3 |
| **macOS** | ✅ PHP 8.2 · ✅ PHP 8.3 | ✅ PHP 8.2 · ✅ PHP 8.3 |

Más la imagen Docker (Linux/amd64) que corre igual en local (Docker Desktop en Windows), en el CI y en Render.

### 16. Problemas encontrados y soluciones aplicadas
- **[`docs/problemas.md`](problemas.md): 41 problemas** documentados con síntoma, causa, solución y lección. Destacados:
  - **P-12:** bug real de producción (crear órdenes daba 500 en PostgreSQL), detectado al probar contra PostgreSQL.
  - **P-35 / P-41:** PostgreSQL en macOS y caída de Chocolatey en Windows (matriz multiplataforma).
  - **P-36 / P-37 / P-38:** primer deploy a Render (falta de `APP_KEY`, *exit 126* por *capabilities* de Linux, ambiente sin datos de prueba).
  - **P-39 / P-40:** fallos del propio pipeline (verificación en redeploys y descarga de artefactos), detectados y corregidos.

---

## Capturas a incluir

Abre cada enlace y toma la captura indicada:

| # | Qué capturar | Dónde |
|---|---|---|
| 1 | Lista de ejecuciones del workflow (verdes y rojas) | https://github.com/Sosa1716/LaravelApp/actions/workflows/deploy.yml |
| 2 | **Diagrama del pipeline de staging**, todos los jobs en verde | [Run 45329866116](https://github.com/Sosa1716/LaravelApp/actions/runs/45329866116), vista principal |
| 3 | **Matriz multiplataforma** (12 jobs `Tests (...)` en verde) | Mismo run, lista de jobs a la izquierda |
| 4 | Job *Deploy (Render)* con los 5 `OK` de la verificación de staging | Run 45329866116 → *Deploy (Render)* → paso *Verificación post-deploy* |
| 5 | Sección **Artifacts** del run | Run 45329866116, abajo del todo |
| 6 | **Release v1.0.0** con sus archivos adjuntos | https://github.com/Sosa1716/LaravelApp/releases/tag/v1.0.0 |
| 7 | Imagen Docker en GHCR con sus tags | https://github.com/users/Sosa1716/packages/container/package/laravelapp |
| 8 | Pipeline de **producción** en verde + verificación | [Run 45331103311](https://github.com/Sosa1716/LaravelApp/actions/runs/45331103311) → *Deploy (Render)* |
| 9 | Environments `staging` y `production` con sus deploys | https://github.com/Sosa1716/LaravelApp/deployments |
| 10 | `/api/version` de staging y de producción en el navegador | Las dos URLs + `/api/version` |
| 11 | Un run **fallido** que bloqueó la liberación (ej. falta de `APP_KEY`) | [Run 45287966814](https://github.com/Sosa1716/LaravelApp/actions/runs/45287966814) |
| 12 | Lista de **Pull Requests** del flujo de ramas | https://github.com/Sosa1716/LaravelApp/pulls?q=is%3Apr+is%3Amerged |
| 13 | Ramas del repositorio | https://github.com/Sosa1716/LaravelApp/branches |
| 14 | Servicios en el **dashboard de Render** (lo toma Sosa) | dashboard.render.com |
