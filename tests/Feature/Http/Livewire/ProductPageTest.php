<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Livewire;

use App\Livewire\ProductPage;
use App\Models\ProductOption as AppProductOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Currency;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductVariant;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        Language::factory()->create(['default' => true]);
        $this->currency = Currency::factory()->create(['default' => true]);
    }

    /**
     * Creates a simple product with one variant and no option values.
     */
    private function createStandardProduct(): Product
    {
        $product = Product::factory()
            ->hasUrls(1, ['default' => true])
            ->has(
                ProductVariant::factory()->afterCreating(function (ProductVariant $variant) {
                    $variant->prices()->create(
                        Price::factory()->make(['currency_id' => $this->currency->id])->getAttributes(),
                    );
                }),
                'variants',
            )
            ->create();

        $product->addMedia(UploadedFile::fake()->image('product.jpg'))->toMediaCollection('images');

        return $product;
    }

    /**
     * Creates a product with uso + tipo-de-lente options linked via a variant.
     */
    private function createLensProduct(): array
    {
        /** @var AppProductOption $usoOption */
        $usoOption = AppProductOption::factory()->create([
            'handle' => 'uso',
            'name'   => ['en' => 'Uso'],
        ]);

        /** @var AppProductOption $lensOption */
        $lensOption = AppProductOption::factory()->create([
            'handle'    => 'tipo-de-lente',
            'name'      => ['en' => 'Tipo de Lente'],
            'parent_id' => $usoOption->id,
        ]);

        $usoValue = ProductOptionValue::factory()->create([
            'product_option_id' => $usoOption->id,
            'name'              => ['en' => 'Solar'],
        ]);

        $lensValue = ProductOptionValue::factory()->create([
            'product_option_id' => $lensOption->id,
            'name'              => ['en' => 'Polarizado'],
        ]);

        $product = Product::factory()
            ->hasUrls(1, ['default' => true])
            ->create();

        $product->addMedia(UploadedFile::fake()->image('product.jpg'))->toMediaCollection('images');

        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 10]);
        $variant->prices()->create(
            Price::factory()->make(['currency_id' => $this->currency->id])->getAttributes(),
        );
        $variant->values()->attach([$usoValue->id, $lensValue->id]);

        return compact('product', 'usoOption', 'lensOption', 'usoValue', 'lensValue', 'variant');
    }

    // -------------------------------------------------------------------------
    // Task 3.1 — Standard product shows standard CTA (regression guard)
    // -------------------------------------------------------------------------

    public function test_shows_standard_cta_when_product_has_no_uso_option(): void
    {
        $product = $this->createStandardProduct();

        Livewire::test(ProductPage::class, ['slug' => $product->defaultUrl->slug])
            ->assertDontSee('Solo Marco')
            ->assertDontSee('Agregar con Lente');
    }

    // -------------------------------------------------------------------------
    // Task 3.2 — hasLensOption returns true with uso + tipo-de-lente
    // -------------------------------------------------------------------------

    public function test_shows_dual_cta_when_product_has_uso_and_tipo_de_lente_options(): void
    {
        ['product' => $product] = $this->createLensProduct();

        Livewire::test(ProductPage::class, ['slug' => $product->defaultUrl->slug])
            ->assertSee('Solo Marco')
            ->assertSee('Agregar con Lente');
    }

    // -------------------------------------------------------------------------
    // Task 3.3 — hasLensOption returns false when uso has no tipo-de-lente child
    // -------------------------------------------------------------------------

    public function test_shows_standard_cta_when_product_has_uso_but_no_tipo_de_lente_child(): void
    {
        /** @var AppProductOption $usoOption */
        $usoOption = AppProductOption::factory()->create([
            'handle' => 'uso',
            'name'   => ['en' => 'Uso'],
        ]);

        $usoValue = ProductOptionValue::factory()->create([
            'product_option_id' => $usoOption->id,
            'name'              => ['en' => 'Solar'],
        ]);

        $product = Product::factory()
            ->hasUrls(1, ['default' => true])
            ->create();

        $product->addMedia(UploadedFile::fake()->image('product.jpg'))->toMediaCollection('images');

        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 10]);
        $variant->prices()->create(
            Price::factory()->make(['currency_id' => $this->currency->id])->getAttributes(),
        );
        $variant->values()->attach([$usoValue->id]);

        Livewire::test(ProductPage::class, ['slug' => $product->defaultUrl->slug])
            ->assertDontSee('Solo Marco')
            ->assertDontSee('Agregar con Lente');
    }

    // -------------------------------------------------------------------------
    // Task 3.4 — addWithLens dispatches add-to-cart on match
    // -------------------------------------------------------------------------

    public function test_add_with_lens_dispatches_event_when_variant_found(): void
    {
        ['product' => $product, 'usoValue' => $usoValue, 'lensValue' => $lensValue] = $this->createLensProduct();

        $cartMock = \Mockery::mock(Cart::class)->makePartial();
        $cartMock->shouldReceive('add')->andReturnSelf();

        CartSession::shouldReceive('manager')->andReturn($cartMock);

        Livewire::test(ProductPage::class, ['slug' => $product->defaultUrl->slug])
            ->call('addWithLens', $usoValue->id, $lensValue->id)
            ->assertDispatched('add-to-cart');
    }

    // -------------------------------------------------------------------------
    // Task 3.5 — addWithLens sets error when no variant matches
    // -------------------------------------------------------------------------

    public function test_add_with_lens_sets_error_when_no_matching_variant(): void
    {
        ['product' => $product] = $this->createLensProduct();

        Livewire::test(ProductPage::class, ['slug' => $product->defaultUrl->slug])
            ->call('addWithLens', 99999, 99998)
            ->assertHasErrors('lens')
            ->assertNotDispatched('add-to-cart');
    }

    // -------------------------------------------------------------------------
    // Task 3.6 — addFrameOnly dispatches add-to-cart
    // -------------------------------------------------------------------------

    public function test_add_frame_only_dispatches_event(): void
    {
        ['product' => $product] = $this->createLensProduct();

        $cartMock = \Mockery::mock(Cart::class)->makePartial();
        $cartMock->shouldReceive('add')->andReturnSelf();

        CartSession::shouldReceive('manager')->andReturn($cartMock);

        Livewire::test(ProductPage::class, ['slug' => $product->defaultUrl->slug])
            ->call('addFrameOnly')
            ->assertDispatched('add-to-cart');
    }

    // -------------------------------------------------------------------------
    // Task 3.7 — standard product unaffected (regression guard)
    // -------------------------------------------------------------------------

    public function test_standard_product_add_to_cart_unaffected(): void
    {
        $product = $this->createStandardProduct();

        Livewire::test(ProductPage::class, ['slug' => $product->defaultUrl->slug])
            ->assertViewIs('livewire.product-page')
            ->assertSee($product->translateAttribute('name'));
    }
}
