# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y
[Versionado Semántico](https://semver.org/lang/es/).
El pipeline toma la sección de la versión correspondiente como notas del GitHub Release.

## [1.0.0]

Primera versión entregada con flujo DevOps completo.

### Añadido
- API REST de restaurante: autenticación con Sanctum, roles (Administrador, Mesero, Cocina),
  categorías, platillos, mesas, órdenes y pagos.
- Pipeline único de CI/CD en `.github/workflows/deploy.yml`:
  - Lint (Laravel Pint), `composer validate` y `composer audit`.
  - Matriz de pruebas Ubuntu/Windows/macOS × PHP 8.2/8.3 × SQLite/PostgreSQL.
  - Cobertura de código, reportes JUnit y paquete de la aplicación como artefactos.
  - Imagen Docker (FrankenPHP) con smoke test, publicada en GHCR con SBOM y provenance.
  - Despliegue a Render staging/producción con verificación post-deploy.
  - Tag y release automáticos solo si staging pasa la verificación.
- Endpoint `GET /api/version` para verificar qué commit está desplegado.
- `docker-compose.yml` para desarrollo local con PostgreSQL 16.
- Entorno de staging en Render.

### Corregido
- Crear órdenes fallaba con 500 en PostgreSQL por la secuencia de `ordenes` (P-12).
- Los seeders se ejecutaban en cada arranque de producción (P-06).
- Dependencias con CVEs conocidos (`laravel/framework`, `symfony/http-foundation`, `symfony/http-kernel`).

### Seguridad
- `APP_KEY` fuera del repositorio y `APP_DEBUG=false` en producción.
- Contenedor ejecutado con usuario sin privilegios.

Detalle de cada problema y su solución: [`docs/problemas.md`](docs/problemas.md).
