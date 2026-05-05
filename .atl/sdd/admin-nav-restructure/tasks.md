# Tasks: admin-nav-restructure

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~60 (55 additions + ~5 deletions) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Delivery strategy | ask-on-risk |
| Suggested split | Single PR |

Decision needed before apply: No
Chained PRs recommended: No
400-line budget risk: Low

---

## Phase 1: Discovery — Read Vendor Reference Files

- [ ] 1.1 Read `vendor/lunarphp/lunar/src/Filament/Resources/ProductOptionResource.php` — record `getUrl('index')`, `navigationIcon`, `navigationSort`, `navigationLabel`
- [ ] 1.2 Read `vendor/lunarphp/lunar/src/Filament/Resources/CustomerGroupResource.php` — same checks
- [ ] 1.3 Read `vendor/lunarphp/lunar/src/Filament/Resources/ProductResource.php` — record `getUrl`, `navigationIcon`, `navigationSort` values
- [ ] 1.4 Read `vendor/lunarphp/lunar/src/Filament/Resources/CustomerResource.php` — same checks
- [ ] 1.5 Read `vendor/lunarphp/lunar/src/LunarPanelManager.php` — note current `navigationGroups` registration
- [ ] 1.6 Read `vendor/filament/filament/src/Navigation/NavigationBuilder.php` — confirm API for `parentItem()`, `group()`, `sort()`
- [ ] 1.7 Read `app/Providers/AppServiceProvider.php` — understand existing panel callback structure
- [ ] 1.8 Read `app/Filament/Resources/BannerResource.php` — note current properties and structure

---

## Phase 2: Core Implementation

- [ ] 2.1 Modify `app/Filament/Resources/BannerResource.php` — add `protected static ?string $navigationGroup = 'Configuración Web';` after `$navigationIcon` or near class top
- [ ] 2.2 Modify `app/Providers/AppServiceProvider.php` — add imports: `NavigationGroup`, `NavigationItem`, `CustomerGroupResource`, `CustomerResource`, `ProductOptionResource`, `ProductResource`
- [ ] 2.3 Modify `app/Providers/AppServiceProvider.php` — in `boot()` method, add `Closure::bind()` calls to suppress ProductOptionResource and CustomerGroupResource navigation registration
- [ ] 2.4 Modify `app/Providers/AppServiceProvider.php` — in the panel callback, add Spanish `NavigationGroup` objects for Catálogo, Ventas, Configuraciones (collapsed), and Configuración Web
- [ ] 2.5 Modify `app/Providers/AppServiceProvider.php` — in the panel callback, add `NavigationItem` for ProductOption with `->parentItem(__('lunarpanel::product.plural_label'))` under Catálogo
- [ ] 2.6 Modify `app/Providers/AppServiceProvider.php` — add `NavigationItem` for CustomerGroup with `->parentItem(__('lunarpanel::customer.plural_label'))` under Ventas

---

## Phase 3: Testing

- [ ] 3.1 Read `tests/Feature/AdminPanelBrandingTest.php` — understand test structure and helper methods used
- [ ] 3.2 Update/add test in `tests/Feature/AdminPanelBrandingTest.php` — assert `BannerResource::$navigationGroup === 'Configuración Web'`
- [ ] 3.3 Update/add test — assert ProductOptionResource does NOT auto-register navigation (after `Closure::bind()` suppression)
- [ ] 3.4 Update/add test — assert CustomerGroupResource does NOT auto-register navigation
- [ ] 3.5 Update/add test — assert ProductOption appears as child of Product via `parentItem` in Catálogo group
- [ ] 3.6 Update/add test — assert CustomerGroup appears as child of Customer via `parentItem` in Ventas group
- [ ] 3.7 Run existing test suite: `php artisan test --compact` to verify no regressions

---

## Implementation Order

1. Phase 1 (vendor reads) must complete before Phase 2 — confirms exact URL routes, icon names, sort orders to use in Phase 2.
2. Phase 2.1 (BannerResource) is independent and can be done first.
3. Phase 2.2–2.6 (AppServiceProvider) depend on knowing the exact translation keys and URLs from Phase 1.
4. Phase 3 (tests) runs last as integration verification.

---

## Verification Per Task

| Task | How to Verify |
|------|---------------|
| 1.1–1.8 | Record values; use in Phase 2 |
| 2.1 | `grep -n 'navigationGroup' app/Filament/Resources/BannerResource.php` |
| 2.2 | `grep -n 'use.*NavigationGroup\|use.*NavigationItem' app/Providers/AppServiceProvider.php` |
| 2.3 | `grep -n 'Closure::bind\|shouldRegisterNavigation' app/Providers/AppServiceProvider.php` |
| 2.4 | `grep -n 'navigationGroups\|Configuración Web\|Catálogo\|Ventas' app/Providers/AppServiceProvider.php` |
| 2.5–2.6 | `grep -n 'navigationItems\|parentItem' app/Providers/AppServiceProvider.php` |
| 3.1–3.7 | `php artisan test --compact --filter=AdminPanel` |