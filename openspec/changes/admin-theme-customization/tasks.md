# Tasks: Admin Theme Customization

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~70 (15 code + 50 test) |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | exception-ok |

Decision needed before apply: No
Chained PRs recommended: No
400-line budget risk: Low

## Phase 1: Core Files

- [x] 1.1 Fix `.env` `APP_NAME` typo: `"Optica Gumzan"` → `"Óptica Guzmán"`
- [x] 1.2 Fix `.env.example` `APP_NAME` typo: same fix for consistency

## Phase 2: AppServiceProvider Changes

- [x] 2.1 Add `use Illuminate\Support\HtmlString;` import
- [x] 2.2 Chain 7 branding methods after `->path('panel')`:
  - `->brandName('Óptica Guzmán')`
  - `->brandLogo(fn () => new HtmlString(view('components.brand.logo')->render()))`
  - `->darkModeBrandLogo(fn () => new HtmlString(view('components.brand.logo')->render()))`
  - `->favicon(asset('favicon.png'))`
  - `->brandLogoHeight('2.5rem')`
  - `->colors(['primary' => '#427318'])`
  - `->font(null)`

## Phase 3: Testing

- [x] 3.1 Write integration test: `LunarPanel::panel()` registers without exceptions and `brandName` returns `'Óptica Guzmán'`

## Phase 4: Polish

- [ ] 4.1 Run `vendor/bin/pint --dirty --format agent` — BLOCKED: environment has PHP 8.3, project requires PHP 8.4
- [ ] 4.2 Run integration test via `php artisan test --compact` — BLOCKED: same PHP version mismatch
- [ ] 4.3 Manual visual verification: open `/panel`, confirm green primary, SVG logo in sidebar, favicon in tab, dark mode toggle works