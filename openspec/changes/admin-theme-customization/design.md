# Design: Admin Theme Customization

## Technical Approach

Modify two files — `.env` and `AppServiceProvider.php` — to rebrand the Lunar/Filament admin panel using exclusively the Fluent API of `Filament\Panel`. No custom CSS theme, no build step, no new files. The SVG logo already uses `fill="currentColor"` so it adapts to light/dark automatically, eliminating the need for a dark variant.

## Architecture Decisions

### Decision: Panel Methods Only vs Custom Theme CSS

| Option | Tradeoff | Decision |
|--------|----------|----------|
| **Panel Methods Only** — `brandName()`, `colors()`, `font()`, etc. on `Filament\Panel` | Zero build step, trivial rollback (2 files), Filament maintains CSS | ✅ Chosen |
| Custom Theme CSS — compile Filament's theme with Tailwind overrides | Full control over every component, but requires Node build pipeline, `tailwind.config.js`, postcss, and ongoing maintenance when Filament updates | ❌ Rejected |

**Rationale**: All 7 requirements are achievable via Panel methods. Adding a build pipeline for 2 color values and a font change is over-engineering. If future needs require deep CSS overrides, the custom theme can be introduced then — without conflict.

### Decision: Dark Mode — Enabled (not disabled)

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `darkMode(false)` — disables toggle | Proposal's original approach; avoids dark-mode visual issues | ❌ Rejected by spec |
| **No `darkMode()` call** — leave default (enabled) | Logo adapts via `currentColor`; users keep toggle | ✅ Chosen |

**Rationale**: The spec explicitly mandates dark mode stays active. The `currentColor`-based logo handles both modes without a dedicated dark variant. No `darkMode()` call needed — Filament's default is enabled.

### Decision: `brandName('Óptica Guzmán')` — literal string, not `config('app.name')`

| Option | Tradeoff | Decision |
|--------|----------|----------|
| **Literal string** `'Óptica Guzmán'` | Guarantees correct UTF-8 regardless of `.env` encoding issues | ✅ Chosen |
| `config('app.name')` | DRY but fragile — `.env` encoding can break accented chars | ❌ Rejected |

**Rationale**: `.env` files are not guaranteed UTF-8-safe across all deployment tools. A literal string in PHP is always correct. The `.env` is still fixed for other consumers (`config('app.name')`), but `brandName()` uses the literal to be safe.

## Data Flow

```
.env (APP_NAME)
  └─→ config('app.name')          ← other consumers
       └─ (NOT used by brandName) ← uses literal string instead

resources/views/components/brand/logo.blade.php
  └─→ view('components.brand.logo')  ← Blade component
       └─→ render() → HTML string with <svg fill="currentColor">
            └─→ brandLogo(fn() => new \Illuminate\Support\HtmlString(...))
                 └─→ Filament injects into sidebar <header>

public/favicon.png
  └─→ asset('favicon.png') → full URL
       └─→ favicon() → browser tab icon

Panel closure chain:
  $panel->plugins([...])
        ->resources([...])
        ->path('panel')
        ->brandName('Óptica Guzmán')
        ->brandLogo(fn() => HtmlString(view('components.brand.logo')->render()))
        ->darkModeBrandLogo(fn() => HtmlString(view('components.brand.logo')->render()))
        ->favicon(asset('favicon.png'))
        ->brandLogoHeight('2.5rem')
        ->colors(['primary' => '#427318'])
        ->font(null)
```

Note: `darkModeBrandLogo()` uses the same SVG component. Since `fill="currentColor"` inherits the computed text color from CSS, the logo automatically picks up light text in dark mode and dark text in light mode — no separate asset needed.

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `.env` | Modify | Fix `APP_NAME` from `"Optica Gumzan"` to `"Óptica Guzmán"` |
| `app/Providers/AppServiceProvider.php` | Modify | Add 7 branding methods to the `LunarPanel::panel()` closure; add `use Illuminate\Support\HtmlString` import |

No new files. No migrations. No deletions.

## Implementation Detail

### `.env` change

```env
# Before
APP_NAME="Optica Gumzan"

# After
APP_NAME="Óptica Guzmán"
```

### `AppServiceProvider.php` — closure additions

The methods are chained on the existing `$panel` fluent builder. Order does not matter (Fluent API). New methods are added after the existing `->path('panel')`:

```php
LunarPanel::panel(
    fn ($panel) => $panel
        ->plugins([
            new ShippingPlugin,
        ])
        ->resources([
            BannerResource::class,
        ])
        ->path('panel')
        ->brandName('Óptica Guzmán')
        ->brandLogo(fn () => new HtmlString(view('components.brand.logo')->render()))
        ->darkModeBrandLogo(fn () => new HtmlString(view('components.brand.logo')->render()))
        ->favicon(asset('favicon.png'))
        ->brandLogoHeight('2.5rem')
        ->colors(['primary' => '#427318'])
        ->font(null),
)
```

New import required: `use Illuminate\Support\HtmlString;`

Key points:
- `brandLogo()` and `darkModeBrandLogo()` receive a Closure returning `HtmlString`. The `HtmlString` wrapper prevents Blade double-escaping the SVG markup.
- `font(null)` removes Filament's default Poppins — Filament falls back to the browser's system font stack.
- `favicon(asset('favicon.png'))` — `asset()` generates the full URL because `favicon.png` lives in `public/`.
- No `darkMode()` call — Filament's default keeps dark mode enabled.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Integration | Panel registers without errors | PHPUnit test: call `LunarPanel::panel()` and assert the closure configures branding methods without exceptions |
| Integration | `brandName` returns expected value | Test that resolves the panel and checks `getBrandName()` equals `'Óptica Guzmán'` |
| Visual | Color, logo, favicon render correctly | Manual: open `/panel`, verify green primary, SVG logo in sidebar, favicon in tab |
| Visual | Dark mode toggle works, logo adapts | Manual: toggle dark mode, confirm logo color flips via `currentColor` |

Note: Visual tests are manual because Filament panel rendering requires a full browser. The integration tests cover that the Panel object is configured correctly.

## Migration / Rollout

No migration required. Rollback = revert 2 files (`AppServiceProvider.php` + `.env`) to their previous state. Zero new files to delete.

## Open Questions

None — all decisions resolved between proposal and spec.