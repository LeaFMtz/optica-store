<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontFooterBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_pages_render_through_layout_that_includes_footer(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('catalog.view'))->assertOk();

        $appJs = file_get_contents(resource_path('js/app.js'));
        $layout = file_get_contents(resource_path('js/Layouts/StorefrontLayout.vue'));

        $this->assertStringContainsString('StorefrontLayout', $appJs);
        $this->assertStringContainsString("import AppFooter from './AppFooter.vue'", $layout);
        $this->assertStringContainsString('<AppFooter />', $layout);
    }

    public function test_footer_shows_orquesta_text_instead_of_logo_image(): void
    {
        $footer = file_get_contents(resource_path('js/Layouts/AppFooter.vue'));

        $this->assertMatchesRegularExpression('/<span class="[^"]*text-white[^"]*">Orquesta<\/span>/', $footer);
        $this->assertStringNotContainsString('/images/logo.webp', $footer);
        $this->assertStringNotContainsString('<img', $footer);
    }
}
