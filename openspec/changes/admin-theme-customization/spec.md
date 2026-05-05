# Admin Branding Specification

## Purpose

Define the visual identity of the Lunar/Filament admin panel to match Óptica Guzmán's brand — name, logo, favicon, primary color, and typography.

## Requirements

### Requirement: Brand Name Display

The admin panel MUST display "Óptica Guzmán" as the brand name in the sidebar header.

The brand name MUST use the accented characters "Ó" and "á" correctly — the typo "Optica Gumzan" in `.env` MUST be corrected.

The `.env` APP_NAME correction MUST NOT affect `config('app.name')` consumers outside the admin panel.

#### Scenario: Brand name rendered in sidebar

- GIVEN the admin panel is loaded
- WHEN a user views the sidebar
- THEN the brand name reads "Óptica Guzmán"

#### Scenario: APP_NAME typo corrected

- GIVEN `.env` contains `APP_NAME="Optica Gumzan"`
- WHEN the `.env` file is updated
- THEN `APP_NAME` reads `"Óptica Guzmán"`
- AND `config('app.name')` returns "Óptica Guzmán"

### Requirement: Brand Logo Rendering

The admin panel MUST render the existing `components.brand.logo` Blade component as the brand logo in the sidebar for both light and dark modes.

Both `brandLogo()` and `darkModeBrandLogo()` MUST reference the same SVG component, which uses `fill="currentColor"` to adapt its color automatically.

The logo MUST maintain its aspect ratio at `brandLogoHeight('2.5rem')` without visual distortion.

#### Scenario: Logo visible in light mode

- GIVEN the admin panel renders in light mode
- WHEN a user views the sidebar
- THEN the brand logo SVG is displayed with dark fill color

#### Scenario: Logo visible in dark mode

- GIVEN the admin panel renders in dark mode
- WHEN a user views the sidebar
- THEN the brand logo SVG is displayed with a light fill color adapted by `currentColor`

#### Scenario: Sidebar collapsed

- GIVEN the admin panel sidebar is collapsed
- WHEN a user views the collapsed sidebar
- THEN the brand logo remains visible and legible at the configured height

#### Scenario: SVG component fails to render

- GIVEN the `components.brand.logo` Blade component is missing or throws an error
- WHEN the admin panel attempts to render the logo
- THEN the brand name text "Óptica Guzmán" MUST still display as fallback (Filament default behavior)

### Requirement: Favicon Configuration

The admin panel MUST use `public/favicon.png` as the browser tab favicon.

#### Scenario: Favicon displayed in browser tab

- GIVEN the admin panel is loaded
- WHEN a user opens the panel in a browser
- THEN the browser tab displays `favicon.png` as the favicon

### Requirement: Primary Color

The admin panel MUST use `#427318` as the primary color for all Filament components that reference the `primary` color slot — including buttons, active links, badges, and form controls.

The color `#427318` MUST provide at least WCAG AA contrast (4.5:1) against white backgrounds for normal text.

#### Scenario: Primary color applied to interactive elements

- GIVEN the admin panel is configured with `colors(['primary' => '#427318'])`
- WHEN a user views Filament buttons, active navigation links, or badges
- THEN those elements use `#427318` as their background or text color

#### Scenario: Primary color contrast compliance

- GIVEN the primary color is `#427318`
- WHEN contrast ratio is measured against white (#FFFFFF)
- THEN the ratio is at least 4.5:1 (WCAG AA)

### Requirement: System Font Stack

The admin panel MUST use the system font stack (no custom font loaded). `font(null)` MUST be called to remove Filament's default Poppins.

#### Scenario: System font renders in admin panel

- GIVEN `font(null)` is configured on the panel
- WHEN a user loads the admin panel
- THEN text renders using the browser's system font stack (no Poppins web font loaded)

### Requirement: Dark Mode Preserved

Dark mode MUST remain enabled in the admin panel. The `darkMode()` method SHALL NOT be called with `false`.

The brand logo uses `fill="currentColor"`, which adapts to the active theme automatically — no dedicated dark variant is needed.

#### Scenario: Dark mode toggle available

- GIVEN the admin panel is configured
- WHEN a user accesses the panel appearance settings
- THEN the dark mode toggle is available and functional

#### Scenario: Dark mode active by default or toggle

- GIVEN a user has dark mode active in their browser preferences or panel settings
- WHEN the admin panel renders
- THEN the panel displays in dark mode with the logo adapting via `currentColor`

### Requirement: Existing Resources Compatibility

The branding customizations MUST NOT break existing panel resources: `BannerResource` and `ShippingPlugin`.

#### Scenario: BannerResource remains functional

- GIVEN `BannerResource::class` is registered in the panel
- WHEN branding methods are added to the panel closure
- THEN BannerResource loads and operates without errors

#### Scenario: ShippingPlugin remains functional

- GIVEN `ShippingPlugin` is registered in the panel plugins
- WHEN branding methods are added to the panel closure
- THEN ShippingPlugin loads and operates without errors