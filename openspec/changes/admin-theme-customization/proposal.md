# Proposal: Admin Theme Customization

## Intent

Personalizar la estética del panel de administración Lunar/Filament para que sea coherente con la identidad visual de Óptica Guzmán — reemplazando el branding "Lunar" (logo, nombre, color Sky azul, font Poppins) por los colores verdes, logo y nombre correcto del negocio.

## Scope

### In Scope
- Corregir `APP_NAME` en `.env` de "Optica Gumzan" → "Óptica Guzmán"
- Agregar personalizaciones al closure de `LunarPanel::panel()` en `AppServiceProvider.php`: `brandName()`, `brandLogo()`, `darkModeBrandLogo()`, `favicon()`, `brandLogoHeight()`, `colors()`, `darkMode()`, `font()`
- Usar color primario `#427318` (WCAG AA, 5.4:1) en lugar de `#71C229` (2.2:1, fails AA)
- Deshabilitar dark mode temporalmente (el logo SVG usa `currentColor` pero no hay variante dark dedicada)

### Out of Scope
- Custom theme CSS (Filament theme compilation)
- Modificación de templates Blade del admin
- Cambios en estructura de navegación o permisos
- Creación de nuevos assets (logo, favicon ya existen)

## Capabilities

### New Capabilities
- `admin-branding`: Personalización visual del panel admin — nombre, logo, favicon, colores primarios, tipografía

### Modified Capabilities
None

## Approach

**Panel Methods Only** — sin custom theme CSS. Usar los métodos nativos de `Filament\Panel` dentro del closure existente en `AppServiceProvider::register()`:

- `brandName('Óptica Guzmán')` — nombre correcto con acentos
- `brandLogo()` / `darkModeBrandLogo()` — renderizar el Blade component `components.brand.logo` existente
- `favicon(asset('favicon.png'))` — PNG que ya está en `public/`
- `brandLogoHeight('2.5rem')` — ajuste visual
- `colors(['primary' => '#427318'])` — verde oscuro con contraste AA
- `darkMode(false)` — deshabilitado hasta tener logo dark dedicado
- `font(null)` — quitar Poppins, usar system fonts como el sitio público

Color primario elegido: `#427318` (primary.700 de la paleta existente). Contraste 5.4:1 sobre blanco — cumple WCAG AA. El verde original `#71C229` tiene solo 2.2:1 y falla AA.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `.env` | Modified | Corregir APP_NAME typo |
| `app/Providers/AppServiceProvider.php` | Modified | Agregar métodos de branding al closure de LunarPanel::panel() |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Contraste insuficiente del color primario en ciertos componentes Filament | Low | Se usa `#427318` (5.4:1), no `#71C229`. Verificar visualmente post-deploy |
| Logo SVG no visible correctamente en sidebar colapsada | Med | Probar `brandLogoHeight` y ajustar si es necesario |
| Dark mode deshabilitado puede confundir admins que lo usaban | Low | Re-habilitar cuando se cree logo dark variant. Transición畔 sencilla |

## Rollback Plan

Revertir el closure en `AppServiceProvider.php` a su estado original (3 líneas: plugins, resources, path) y restaurar `APP_NAME` en `.env`. Sin migraciones ni archivos nuevos — rollback limpio.

## Dependencies

- Filament Panel API (métodos `brandName`, `brandLogo`, `colors`, `font`, `darkMode`, `favicon`)
- Assets existentes: `resources/views/components/brand/logo.blade.php`, `public/favicon.png`

## Success Criteria

- [ ] El panel admin muestra "Óptica Guzmán" como nombre de marca
- [ ] El logo SVG aparece correctamente en la sidebar
- [ ] El color primario es verde (#427318) en botones, links y estados activos
- [ ] El favicon del panel es el icono de Óptica Guzmán
- [ ] Se usa system font en vez de Poppins
- [ ] Dark mode está deshabilitado
- [ ] `APP_NAME` corregido en `.env`