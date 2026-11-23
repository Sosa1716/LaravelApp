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

---

## Pendientes detectados (se resuelven en fases posteriores)

| ID | Problema | Fase |
|----|----------|------|
| P-06 | `Dockerfile` ejecuta `db:seed --force` en **cada arranque** (duplica datos / crea usuarios con contraseña `12345678` en producción). | 2 |
| P-07 | Se usa `php artisan serve` (servidor de desarrollo) y `chmod 777` en la imagen. | 2 |
| P-08 | Las pruebas solo corren en SQLite, producción usa PostgreSQL. | 1 |
| P-09 | No existe entorno de staging en Render. | 3 |
