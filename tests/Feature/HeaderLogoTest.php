<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class HeaderLogoTest extends TestCase
{
    public function test_header_uses_orky_text_instead_of_logo_image(): void
    {
        $header = file_get_contents(resource_path('js/Layouts/AppHeader.vue'));

        $this->assertStringContainsString('Orky', $header);
        $this->assertStringNotContainsString("/images/logo.webp", $header);
    }

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }
}
