## Descripción

<!-- ¿Qué cambia y por qué? Enlaza el issue si existe: "Closes #123" -->

## Tipo de cambio

- [ ] Corrección de bug
- [ ] Nueva funcionalidad
- [ ] Refactor / estilo (sin cambio de comportamiento)
- [ ] Infraestructura / CI/CD
- [ ] Documentación

## Rama destino

- [ ] `develop` ← `feature/*`, `fix/*`, `chore/*`
- [ ] `staging` ← `develop` (despliega a staging y, si verifica, crea el release)
- [ ] `main` ← `staging` (despliega a producción tras aprobación)

## Checklist

- [ ] `vendor/bin/pint --test` pasa
- [ ] `php artisan test` pasa en local
- [ ] Añadí o actualicé pruebas
- [ ] Si hay migraciones: funcionan en SQLite **y** PostgreSQL
- [ ] Si cambian variables de entorno: actualicé `.env.example`, `render.yaml` y el README
- [ ] Si resolví un problema no trivial: lo registré en `docs/problemas.md`
- [ ] Actualicé `CHANGELOG.md` si aplica

## Cómo probarlo

<!-- Pasos o peticiones (curl) para verificar el cambio -->
