<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontHeaderBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_through_storefront_layout_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertSame('Home/Index', $response->original->getData()['page']['component']);
    }

    public function test_app_header_shows_orquesta_text_brand_instead_of_logo_image(): void
    {
        $header = file_get_contents(resource_path('js/Layouts/AppHeader.vue'));

        $this->assertSame(3, substr_count($header, '>Orquesta</'));
        $this->assertStringNotContainsString('logo.webp', $header);
        $this->assertSame(2, substr_count($header, '<a href="/" class="block text-center'));
    }
}
