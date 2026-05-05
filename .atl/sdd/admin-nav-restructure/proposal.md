# Proposal: Admin Nav Restructure

## Intent

Reorganize the Lunar admin panel sidebar navigation to group BannerResource under a new "Configuración Web" section, and nest ProductOptionResource and CustomerGroupResource as sub-items under their parent resources (Product and Customer respectively), making the sidebar more logically structured and reducing clutter in the collapsed "Configuraciones" group.

## Scope

### In Scope
- Add `$navigationGroup` to BannerResource so it appears under "Configuración Web"
- Register "Configuración Web" as a navigation group in the Lunar panel
- Hide ProductOptionResource from auto-registered navigation (remove from "Configuraciones")
- Add custom NavigationItem for ProductOption nested under "Productos" in "Catálogo"
- Hide CustomerGroupResource from auto-registered navigation (remove from "Configuraciones")
- Add custom NavigationItem for CustomerGroup nested under "Clientes" in "Ventas"

### Out of Scope
- Modifying any vendor files (lunarphp/lunar)
- Changing routes, permissions, or resource functionality
- Reordering other navigation items
- Adding new resources or pages

## Capabilities

### New Capabilities
- `admin-nav-nesting`: Nested sidebar navigation structure using Filament v4's `parentItem()` mechanism

### Modified Capabilities
- None (existing resource behaviors unchanged; only navigation display is affected)

## Approach

**Filament v4 Navigation API**: Use `NavigationItem::parentItem()` for nesting, NOT `NavigationGroup::url()` (which doesn't exist). Filament's `NavigationManager` automatically groups child items under parents matching by label within the same group.

**Challenge**: `ProductOptionResource::getNavigationGroup()` and `CustomerGroupResource::getNavigationGroup()` are overridden in vendor code to return hardcoded translations. Static property overrides (`navigationGroup()`) are ignored because the method bypasses the property. The `shouldRegisterNavigation` property is `protected`, requiring `Closure::bind()` to modify from outside.

### Change 1: BannerResource → "Configuración Web"

Add `protected static ?string $navigationGroup = 'Configuración Web';` to `BannerResource` and register the group in `AppServiceProvider`.

### Changes 2 & 3: Nested navigation for ProductOption and CustomerGroup

In `AppServiceProvider::boot()`:

1. **Hide vendor resources from auto-navigation** — Use `Closure::bind()` to set `$shouldRegisterNavigation = false` on `ProductOptionResource` and `CustomerGroupResource`. This prevents duplicate entries.

2. **Add custom NavigationItems** — In the `LunarPanel::panel()` callback, add items via `$panel->navigationItems()`:
   - `NavigationItem::make('Opciones de Producto')` with `group(__('lunarpanel::global.sections.catalog'))`, `parentItem(__('lunarpanel::product.plural_label'))`, `url()` pointing to ProductOptionResource index, and appropriate icon/sort.
   - `NavigationItem::make('Grupos de Clientes')` with `group(__('lunarpanel::global.sections.sales'))`, `parentItem(__('lunarpanel::customer.plural_label'))`, `url()` pointing to CustomerGroupResource index, and appropriate icon/sort.

3. **Register navigation groups** — Add all groups in Spanish with proper order and collapsible settings in `LunarPanel::panel()` callback.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Filament/Resources/BannerResource.php` | Modified | Add `$navigationGroup` property |
| `app/Providers/AppServiceProvider.php` | Modified | Add navigation config, hide vendor resources, add custom nav items |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Lunar update changes resource class hierarchy | Low | `Closure::bind()` targets the specific resource class directly; test after upgrades |
| Locale mismatch on `parentItem` label | Low | Use `__()` translation helper matching the parent resource's translated label exactly |
| `Closure::bind()` breaks on PHP version change | Low | This is a standard PHP feature since 5.4, stable in 8.4 |
| Parent item label changes break nesting | Low | Use translation keys matching parent resources; documented in code comments |

## Rollback Plan

1. Remove `$navigationGroup` from BannerResource
2. Remove `Closure::bind()` calls and `navigationItems()` from AppServiceProvider
3. Remove 'Configuración Web' from navigation groups
4. Revert AppServiceProvider to original LunarPanel::panel callback

## Dependencies

- Filament v4 Navigation API (`NavigationItem::parentItem()`, `NavigationItem::childItems()`)
- Lunar admin panel translation keys (`lunarpanel::global.sections.*`, `lunarpanel::product.plural_label`, etc.)

## Success Criteria

- [ ] BannerResource appears under "Configuración Web" group in sidebar
- [ ] ProductOptionResource appears as nested sub-item under "Productos" in "Catálogo"
- [ ] CustomerGroupResource appears as nested sub-item under "Clientes" in "Ventas"
- [ ] Product and Customer parent items remain clickable (link to their index pages)
- [ ] Nested sub-items show expandable chevron
- [ ] No duplicate entries for ProductOption or CustomerGroup
- [ ] Original ProductOption and CustomerGroup routes/URLs still work
- [ ] "Configuraciones" group no longer shows ProductOption or CustomerGroup