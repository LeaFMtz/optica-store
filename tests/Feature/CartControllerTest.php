<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LensType;
use App\Models\LensUse;
use App\Models\ProductLensConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_persists_prescription_data_in_meta(): void
    {
        $variant = $this->makeVariant();
        $config = $this->makeLensConfig($variant);

        $response = $this->postJson('/cart/lines', [
            'variant_id' => $variant->id,
            'quantity' => 1,
            'lens_configuration_id' => $config->id,
            'prescription_data' => [
                'od_esfera' => -1.5,
                'od_cilindro' => -0.5,
                'oi_esfera' => -2.0,
                'oi_cilindro' => 0.0,
            ],
        ]);

        $response->assertOk();

        $line = collect($response->json('lines'))->first();
        $this->assertNotNull($line);
        $this->assertEquals($config->id, $line['meta']['lens_configuration_id']);
        $this->assertEquals(-1.5, $line['meta']['prescription_data']['od_esfera']);
        $this->assertEquals(-0.5, $line['meta']['prescription_data']['od_cilindro']);
        $this->assertEquals(-2.0, $line['meta']['prescription_data']['oi_esfera']);
        $this->assertEquals(0.0, $line['meta']['prescription_data']['oi_cilindro']);
    }

    public function test_store_without_prescription_data_works(): void
    {
        $variant = $this->makeVariant();

        $response = $this->postJson('/cart/lines', [
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response->assertOk();

        $line = collect($response->json('lines'))->first();
        $this->assertNotNull($line);
        $this->assertArrayNotHasKey('prescription_data', $line['meta'] ?? []);
    }

    public function test_store_rejects_non_numeric_prescription_data(): void
    {
        $variant = $this->makeVariant();

        $response = $this->postJson('/cart/lines', [
            'variant_id' => $variant->id,
            'quantity' => 1,
            'prescription_data' => [
                'od_esfera' => 'not-a-number',
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['prescription_data.od_esfera']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Currency::factory()->create(['default' => true]);
        Channel::factory()->create(['default' => true]);
    }

    private function makeVariant(): ProductVariant
    {
        return ProductVariant::factory()->create(['purchasable' => 'always']);
    }

    private function makeLensConfig(ProductVariant $variant): ProductLensConfiguration
    {
        $lensUse = LensUse::create(['name' => 'Visión Simple', 'sort_order' => 1]);
        $lensType = LensType::create(['name' => 'Orgánico', 'handle' => 'organico', 'sort_order' => 1]);

        return ProductLensConfiguration::create([
            'product_id' => $variant->product_id,
            'lens_use_id' => $lensUse->id,
            'lens_type_id' => $lensType->id,
            'crystal_product_id' => $variant->product_id,
            'price_override' => 15000,
        ]);
    }
}
