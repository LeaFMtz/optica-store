# Design: Admin Nav Restructure

## Technical Approach

Modify two files to restructure the Lunar admin panel sidebar: (1) add `$navigationGroup` to `BannerResource`, and (2) in `AppServiceProvider`, suppress vendor resource navigation items via `Closure::bind()`, then add custom `NavigationItem` instances with `parentItem()` to nest ProductOption under Product and CustomerGroup under Customer. The approach leverages Filament v4's built-in `NavigationManager` parent-child matching — items with `parentItem()` set are automatically grouped under a parent with the same label in the same group. No vendor files are modified.

## Architecture Decisions

### Decision 1: Suppress vendor nav items via `Closure::bind()`

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Override `shouldRegisterNavigation` via subclass | Requires new resource classes; routes change | ✗ Rejected |
| `Closure::bind()` on `$shouldRegisterNavigation` | Modifies protected static prop at runtime; no route changes | ✓ Chosen |
| Filter items in custom `NavigationBuilder` | More code; unnecessary complexity | ✗ Rejected |

**Rationale**: `Closure::bind()` directly sets `$shouldRegisterNavigation = false` on each vendor resource class. This prevents `registerNavigationItems()` from adding them to the panel. All resource routes, pages, and CRUD remain functional — only the sidebar entry is suppressed. This is the least-invasive approach since we can't modify vendor files and subclassing would change URL paths.

### Decision 2: Nesting via `parentItem()` (not `childItems()`)

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Set `childItems()` on parent NavigationItem | Require finding/modifying the auto-registered parent item | ✗ Rejected |
| Set `parentItem()` on child NavigationItem | Filament auto-matches by label within group; no parent modification needed | ✓ Chosen |

**Rationale**: Filament's `NavigationManager::get()` (lines 68-79) groups items by `parentItem()` and matches them against parent items by label within the same group. Setting `parentItem(__('lunarpanel::product.plural_label'))` on the ProductOption item causes NavigationManager to find the auto-registered Product item (whose label is the same `__('lunarpanel::product.plural_label')`) and attach our child to it. We never touch the parent items — they auto-register normally and remain fully clickable.

### Decision 3: Spanish navigation groups override

| Option | Tradeoff | Decision |
|--------|----------|----------|
| Keep Lunar default English groups | Spanish item labels won't match; ordering breaks | ✗ Rejected |
| Override groups with Spanish labels + `NavigationGroup` objects | Full control over labels, ordering, collapse behavior | ✓ Chosen |

**Rationale**: The app locale is `es`. Lunar's `defaultPanel()` registers English group labels ('Catalog', 'Sales', 'Settings'), but all vendor resources return translated Spanish labels from `__()`. The mismatch means items create their own groups, defeating ordering. We override with Spanish `NavigationGroup` objects to control labels, order, and collapse behavior.

## Data Flow

```
AppServiceProvider::boot()
│
├── Closure::bind() → ProductOptionResource::$shouldRegisterNavigation = false
├── Closure::bind() → CustomerGroupResource::$shouldRegisterNavigation = false
│
AppServiceProvider::register() → LunarPanel::panel(callback)
│
├── $panel->navigationGroups([Spanish groups + Configuración Web])
├── $panel->navigationItems([ProductOption child, CustomerGroup child])
│
Filament NavigationManager::get()
│
├── Auto-registered items (Product, Customer, Brand, etc.)
├── Custom items (ProductOption child, CustomerGroup child)
│
├── Groups items by parentItem label
│   ├── ProductItem["Productos"] → parentItem match → child: ProductOptionItem["Opciones de Producto"]
│   └── CustomerItem["Clientes"] → parentItem match → child: CustomerGroupItem["Grupos de Clientes"]
│
└── Renders sidebar with nested structure
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `app/Filament/Resources/BannerResource.php` | Modify | Add `$navigationGroup = 'Configuración Web'` property |
| `app/Providers/AppServiceProvider.php` | Modify | Add imports, `Closure::bind()` calls in `boot()`, Spanish `navigationGroups()`, custom `navigationItems()` in panel callback |

## Implementation Details

### AppServiceProvider Changes

**New imports**:
```php
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Lunar\Admin\Filament\Resources\CustomerGroupResource;
use Lunar\Admin\Filament\Resources\CustomerResource;
use Lunar\Admin\Filament\Resources\ProductOptionResource;
use Lunar\Admin\Filament\Resources\ProductResource;
```

**boot() — suppress vendor nav items**:
```php
// Hide ProductOption and CustomerGroup from auto-registered navigation
// These resources' pages/routes remain fully functional
Closure::bind(function () {
    ProductOptionResource::$shouldRegisterNavigation = false;
}, null, ProductOptionResource::class)();

Closure::bind(function () {
    CustomerGroupResource::$shouldRegisterNavigation = false;
}, null, CustomerGroupResource::class)();
```

**Panel callback — navigation groups and items**:
```php
fn ($panel) => $panel
    // ... existing config ...
    ->navigationGroups([
        __('lunarpanel::global.sections.catalog'),                    // Catálogo
        __('lunarpanel::global.sections.sales'),                      // Ventas
        NavigationGroup::make()
            ->label(__('lunarpanel::global.sections.settings'))      // Configuraciones
            ->collapsed(),
        NavigationGroup::make()
            ->label('Configuración Web')
            ->collapsed(),
    ])
    ->navigationItems([
        // ProductOption nested under Product in Catálogo
        NavigationItem::make(__('lunarpanel::productoption.plural_label'))
            ->url(ProductOptionResource::getUrl('index'))
            ->icon(FilamentIcon::resolve('lunar::product-options') ?? 'lucide-list')
            ->group(__('lunarpanel::global.sections.catalog'))
            ->parentItem(__('lunarpanel::product.plural_label'))
            ->sort(2),

        // CustomerGroup nested under Customer in Ventas
        NavigationItem::make(__('lunarpanel::customergroup.plural_label'))
            ->url(CustomerGroupResource::getUrl('index'))
            ->icon(FilamentIcon::resolve('lunar::customer-groups') ?? 'lucide-users')
            ->group(__('lunarpanel::global.sections.sales'))
            ->parentItem(__('lunarpanel::customer.plural_label'))
            ->sort(3),
    ])
```

### BannerResource Changes

Add navigation group property:
```php
protected static ?string $navigationGroup = 'Configuración Web';
```

## Key Technical Notes

1. **`Closure::bind()` timing**: Must run in `boot()` before panel registration. `LunarPanel::register()` is called inside the `register()` method via the stored closure, which runs after `boot()`. However, since `shouldRegisterNavigation` is checked lazily during navigation mounting (not at registration time), setting it in `boot()` is sufficient.

2. **Icon resolution**: `FilamentIcon::resolve('lunar::product-options')` and `FilamentIcon::resolve('lunar::customer-groups')` are already registered in `LunarPanelManager::register()` (lines 160, 149). Using them directly ensures visual consistency with vendor items. Fallback to lucide icons if resolution fails.

3. **`parentItem()` label matching**: The label must EXACTLY match the parent resource's plural label (`__('lunarpanel::product.plural_label')` and `__('lunarpanel::customer.plural_label')`). NavigationManager matches by string equality within the same group. If translations change, these keys must be updated accordingly.

4. **Parent items untouched**: ProductResource and CustomerResource auto-register their navigation items normally. Their items have `url()`, `icon()`, and `group()`. NavigationManager adds `childItems()` to them when it finds a matching `parentItem` label. No modification needed.

5. **Sort values**: Product has `navigationSort = 1`, Customer has `navigationSort = 2`. Child items use `sort(2)` and `sort(3)` respectively to appear after their parents within the group.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `Closure::bind()` sets `$shouldRegisterNavigation = false` | Test that `ProductOptionResource::shouldRegisterNavigation()` returns `false` after binding |
| Unit | `BannerResource::$navigationGroup` equals 'Configuración Web' | Direct property assertion |
| Feature | Sidebar renders with nested structure | Get panel navigation, assert ProductOption appears under Product in Catálogo group |
| Feature | Sidebar renders CustomerGroup under Customer in Ventas | Get panel navigation, assert nesting |
| Feature | BannerResource appears under Configuración Web | Get panel navigation, assert group membership |
| Feature | No duplicate entries | Assert ProductOption and CustomerGroup appear exactly once |
| Feature | Direct URLs still work | GET `/panel/product-options` and `/panel/customer-groups` return 200 |

## Migration / Rollback

No database migration required. Rollback per proposal:
1. Remove `$navigationGroup` from BannerResource
2. Remove `Closure::bind()` calls, `navigationGroups()`, `navigationItems()` from AppServiceProvider
3. Revert AppServiceProvider panel callback to original form

## Open Questions

- [ ] Verify that `ProductOptionResource::getUrl('index')` works outside of a route context (in AppServiceProvider). May need to use `(string) ProductOptionResource::getUrl('index')` or a fallback URL. If it throws, use hardcoded `/panel/product-options` path instead.