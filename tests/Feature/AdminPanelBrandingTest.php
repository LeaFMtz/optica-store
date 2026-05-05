<?php

declare(strict_types=1);

namespace Tests\Feature;

use Filament\Facades\Filament;
use Tests\TestCase;

class AdminPanelBrandingTest extends TestCase
{
    public function test_panel_registers_without_exceptions(): void
    {
        $panel = Filament::getPanel('lunar');

        $this->assertNotNull($panel);
    }

    public function test_brand_name_is_optica_guzman(): void
    {
        $panel = Filament::getPanel('lunar');

        $this->assertSame('Óptica Guzmán', $panel->getBrandName());
    }

    public function test_primary_color_is_configured(): void
    {
        $panel = Filament::getPanel('lunar');

        $colors = $panel->getColors();

        $this->assertArrayHasKey('primary', $colors);
        $this->assertSame('#427318', $colors['primary']);
    }

    public function test_brand_logo_height_is_configured(): void
    {
        $panel = Filament::getPanel('lunar');

        $this->assertSame('2.5rem', $panel->getBrandLogoHeight());
    }

    public function test_favicon_is_configured(): void
    {
        $panel = Filament::getPanel('lunar');

        $this->assertStringContainsString('favicon.png', $panel->getFavicon());
    }

    public function test_font_is_null_for_system_fonts(): void
    {
        $panel = Filament::getPanel('lunar');

        $this->assertNull($panel->getFont());
    }
}
