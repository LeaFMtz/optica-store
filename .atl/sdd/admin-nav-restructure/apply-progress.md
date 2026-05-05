# Apply Progress: admin-nav-restructure

## Phase 1: Discovery — Read Vendor Reference Files

- [x] 1.1 ProductOptionResource: URL=`/panel/product-options`, icon=`lucide-list`, sort=1, group=`settings`
- [x] 1.2 CustomerGroupResource: URL=`/panel/customer-groups`, icon=`lucide-users`, sort=1, group=`settings`
- [x] 1.3 ProductResource: URL=`/panel/products`, icon=`lucide-tag`, sort=1, group=`catalog`
- [x] 1.4 CustomerResource: URL=`/panel/customers`, icon=`lucide-users`, sort=2, group=`sales`
- [x] 1.5 LunarPanelManager: default groups are 'Catalog', 'Sales', 'Settings'(collapsed)
- [x] 1.6 NavigationBuilder: supports `group()`, `items()`, `groups()`, `getNavigation()` with visibility filtering
- [x] 1.7 AppServiceProvider: existing panel callback with resources, path, branding, colors, font
- [x] 1.8 BannerResource: has `$navigationIcon`, `$navigationLabel`, no `$navigationGroup`

## Phase 2: Core Implementation

- [x] 2.1 BannerResource.php: added `protected static ?string $navigationGroup = 'Configuración Web';` after `$navigationIcon`
- [x] 2.2 AppServiceProvider.php: added imports for `Closure`, `NavigationGroup`, `NavigationItem`, `FilamentIcon`, `CustomerGroupResource`, `CustomerResource`, `ProductOptionResource`, `ProductResource`
- [x] 2.3 AppServiceProvider.php: added `Closure::bind()` in `boot()` to suppress ProductOptionResource and CustomerGroupResource navigation
- [x] 2.4 AppServiceProvider.php: added Spanish `navigationGroups()` — Catálogo, Ventas, Configuraciones (collapsed), Configuración Web (collapsed)
- [x] 2.5 AppServiceProvider.php: added NavigationItem for ProductOption with `parentItem(__('lunarpanel::product.plural_label'))` under Catálogo
- [x] 2.6 AppServiceProvider.php: added NavigationItem for CustomerGroup with `parentItem(__('lunarpanel::customer.plural_label'))` under Ventas

## Verification

- [x] `grep navigationGroup` in BannerResource.php — confirmed line 30
- [x] `grep NavigationGroup|NavigationItem` in AppServiceProvider.php — confirmed imports and usage
- [x] `grep Closure::bind|shouldRegisterNavigation` — confirmed both suppression calls
- [x] `grep parentItem|navigationItems` — confirmed nested items

## Notes

- `vendor/bin/pint --dirty --format agent` could not run (environment PHP 8.3 vs required 8.4 + Phar disabled). Code follows PSR-12/Laravel conventions manually.
- Phase 3 (Testing) SKIPPED per instructions — tests are dockerized and will be run separately.
