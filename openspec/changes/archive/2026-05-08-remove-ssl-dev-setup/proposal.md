# Proposal: Eliminar setup SSL de desarrollo + dev-env.sh

## Intent

Quitar todo rastro del proxy SSL de desarrollo local (nginx-ssl container, mkcert, certificados) y eliminar `dev-env.sh` completamente. El SSL proxy y el script de setup SSL agregaban complejidad innecesaria sin beneficio real para desarrollo local. El setup se simplifica a `docker compose up`.

## Scope

### In Scope
- Remover servicio `nginx-ssl` de `docker-compose.yml`
- Borrar directorio `docker/ssl/` completo
- **Eliminar `dev-env.sh` completamente** (no solo modificar)
- Cambiar URLs de `https://ecomm.localhost` a `http://localhost` en `.env`, `.env.example`
- Quitar configuración SSL de `vite.config.js`, cambiar `wss` → `ws`
- Remover dependencia sin uso `@vitejs/plugin-basic-ssl` de `package.json`
- Actualizar `README.md` con instrucciones simplificadas (`docker compose up`)
- Limpiar entradas `.gitignore` relacionadas a certs SSL

### Out of Scope
- No mantener SSL como opción opt-in
- No reemplazar `dev-env.sh` con nuevo script (usar `docker compose up` directamente)
- No tocar configuración SSL de producción

## Capabilities

### New Capabilities
None

### Modified Capabilities
None

## Approach

**Eliminación completa del SSL proxy y `dev-env.sh`**:
- `localhost` está exento de requisitos HTTPS para APIs de navegador (cámara, geolocalización)
- Webhooks de pasarelas de pago en dev se manejan con Stripe CLI/ngrok, no con certs locales
- Exponer puerto 80 del container `app` directamente
- Setup dev simplificado: `cp .env.example .env && docker compose up`
- `dev-env.sh` se elimina porque su única función relevante era generar certs SSL

## Affected Areas

| Area | Impact | Descripción |
|------|--------|-------------|
| `dev-env.sh` | **Removed** | Eliminar completamente |
| `docker-compose.yml` | Modified | Remover `nginx-ssl` service, exponer app:80 |
| `docker/ssl/` | Removed | Borrar directorio completo |
| `.env`, `.env.example` | Modified | Cambiar URLs a `http://localhost`, simplificar ports |
| `vite.config.js` | Modified | Quitar SSL config, `wss` → `ws`, `ecomm.localhost` → `localhost` |
| `package.json` | Modified | Remover `@vitejs/plugin-basic-ssl` sin uso |
| `README.md` | Modified | Actualizar a `docker compose up`, sin mkcert ni HTTPS |
| `.gitignore` | Modified | Remover entradas `docker/ssl/cert.pem`, `docker/ssl/key.pem` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Vite HMR se rompe al cambiar `wss` → `ws` | Med | Verificar configuración HMR: `host: 'localhost'`, `protocol: 'ws'` |
| Otros devs usaban `dev-env.sh` como entry point | Low | Documentar claramente en README: `docker compose up` |
| Otros devs usaban `ecomm.localhost` | Low | Documentar transición |

## Rollback Plan

1. Checkout anterior: `git restore docker-compose.yml dev-env.sh docker/ssl/`
2. Restaurar URLs HTTPS en `.env`/`.env.example`
3. Restaurar SSL config en `vite.config.js`
4. Revertir `package.json`

## Dependencies

Ninguna dependencia nueva. Se eliminan dependencias y scripts.

## Success Criteria

- [ ] `nginx-ssl` service removido de `docker-compose.yml`
- [ ] `docker/ssl/` directorio no existe
- [ ] `dev-env.sh` no existe
- [ ] `APP_URL=http://localhost` en `.env.example`
- [ ] `vite.config.js` no tiene configuración SSL
- [ ] `@vitejs/plugin-basic-ssl` removido de `package.json`
- [ ] `README.md` instruye `docker compose up` sin mkcert ni HTTPS
