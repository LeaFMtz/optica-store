# Tasks: Eliminar setup SSL de desarrollo + dev-env.sh

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~300 lines |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

## Phase 1: Eliminación de infraestructura SSL

- [x] 1.1 Eliminar servicio `nginx-ssl` completo de `docker-compose.yml` (líneas 2-17)
- [x] 1.2 Agregar exposición de puerto 80 al servicio `app` en `docker-compose.yml` (mapeado a 8080 host)
- [x] 1.3 Eliminar `depends_on.nginx-ssl` del servicio `app` (ya no existe)
- [x] 1.4 Eliminar directorio `docker/ssl/` completo (`nginx-ssl.conf`, `cert.pem`, `key.pem`)
- [x] 1.5 Eliminar archivo `dev-env.sh` completamente

## Phase 2: Configuración de la aplicación

- [x] 2.1 En `vite.config.js`: remover import `fs`, remover bloque `hasCert` + `https`, remover bloque `hmr` (usa defaults)
- [x] 2.2 En `package.json`: remover devDependency `@vitejs/plugin-basic-ssl`
- [x] 2.3 En `.gitignore`: remover líneas `/docker/ssl/cert.pem`, `/docker/ssl/key.pem`
- [x] 2.4 En `.env.example`: `APP_URL=http://localhost:8080`, `ASSET_URL=http://localhost:8080`, remover `PORT_HTTP`/`PORT_HTTPS`

## Phase 3: Documentación

- [x] 3.1 En `README.md`: setup simplificado `cp .env.example .env && docker compose up -d`
- [x] 3.2 Eliminar sección "Despliegue Manual" completa (todo el SSL/mkcert)
- [x] 3.3 Actualizar URLs de acceso: `http://localhost:8080`
- [x] 3.4 Actualizar tabla de puertos: App = 8080, Vite = 5173, MySQL = 3306
