<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomeControllerBannerShapeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Create an active hero banner with a desktop image attached.
     */
    private function createHeroBannerWithDesktopImage(): Banner
    {
        /** @var Banner $banner */
        $banner = Banner::create([
            'title' => 'Test Hero Banner',
            'url' => 'https://example.com',
            'position' => 'home_hero',
            'order' => 1,
            'is_active' => true,
        ]);

        $banner->addMedia(UploadedFile::fake()->image('desktop.jpg'))
            ->toMediaCollection('image');

        return $banner;
    }

    public function test_hero_banner_with_desktop_image_only_returns_null_mobile_url(): void
    {
        $this->createHeroBannerWithDesktopImage();

        $response = $this->get('/');

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];

        $this->assertNotEmpty($props['heroBanners']);

        $bannerData = $props['heroBanners'][0];

        $this->assertNotEmpty($bannerData['image_url']);
        $this->assertNull($bannerData['mobile_image_url']);
    }

    public function test_hero_banner_with_both_images_returns_both_urls(): void
    {
        /** @var Banner $banner */
        $banner = $this->createHeroBannerWithDesktopImage();

        $banner->addMedia(UploadedFile::fake()->image('mobile.jpg'))
            ->toMediaCollection('mobile_image');

        $response = $this->get('/');

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];

        $bannerData = $props['heroBanners'][0];

        $this->assertNotEmpty($bannerData['image_url']);
        $this->assertNotEmpty($bannerData['mobile_image_url']);
        $this->assertNotEquals($bannerData['image_url'], $bannerData['mobile_image_url']);
    }

    public function test_no_banners_returns_empty_collections(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];

        $this->assertEmpty($props['heroBanners']);
        $this->assertEmpty($props['middleBanners']);
        $this->assertEmpty($props['bottomBanners']);
        $this->assertNull($props['newsletterBanner']);
    }
}
