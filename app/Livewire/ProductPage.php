<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Traits\FetchesUrls;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Lunar\Facades\CartSession;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductPage extends Component
{
    use FetchesUrls;

    /**
     * The selected option values.
     */
    public array $selectedOptionValues = [];

    public function mount($slug): void
    {
        $this->url = $this->fetchUrl(
            $slug,
            (new Product)->getMorphClass(),
            [
                'element.media',
                'element.variants.basePrices.currency',
                'element.variants.basePrices.priceable',
                'element.variants.values.option.children',
            ],
        );

        if (!$this->url) {
            abort(404);
        }

        $this->selectedOptionValues = $this->productOptions->mapWithKeys(function ($data) {
            return [$data['option']->id => $data['values']->first()->id];
        })->toArray();
    }

    /**
     * Computed property to get variant.
     */
    public function getVariantProperty(): ProductVariant
    {
        return $this->product->variants->first(function ($variant) {
            return !$variant->values->pluck('id')
                ->diff(
                    collect($this->selectedOptionValues)->values(),
                )->count();
        });
    }

    /**
     * Computed property to return all available option values.
     */
    public function getProductOptionValuesProperty(): Collection
    {
        return $this->product->variants->pluck('values')->flatten();
    }

    /**
     * Computed propert to get available product options with values.
     */
    public function getProductOptionsProperty(): Collection
    {
        return $this->productOptionValues->unique('id')->groupBy('product_option_id')
            ->map(function ($values) {
                return [
                    'option' => $values->first()->option,
                    'values' => $values,
                ];
            })->values();
    }

    /**
     * Computed property to return product.
     */
    public function getProductProperty(): Product
    {
        return $this->url->element;
    }

    /**
     * Return all images for the product.
     */
    public function getImagesProperty(): Collection
    {
        return $this->product->media->sortBy('order_column');
    }

    /**
     * Computed property to return current image.
     */
    public function getImageProperty(): ?Media
    {
        if (count($this->variant->images)) {
            return $this->variant->images->first();
        }

        if ($primary = $this->images->first(fn ($media) => $media->getCustomProperty('primary'))) {
            return $primary;
        }

        return $this->images->first();
    }

    /**
     * Returns true if the product has a 'uso' option with at least one
     * 'tipo-de-lente' child option — enabling the dual-CTA configurator.
     */
    public function hasLensOption(): bool
    {
        return $this->productOptionValues
            ->pluck('option')
            ->unique('id')
            ->contains(function ($option) {
                return $option->handle === 'uso'
                    && $option->children->contains('handle', 'tipo-de-lente');
            });
    }

    /**
     * Builds the lens selection map from already-eager-loaded variant data.
     * Shape: { uso_value_id: { uso_name, child_option_name, values: [{id, name}] } }
     *
     * @return array<int, array{uso_name: string, child_option_name: string, values: list<array{id: int, name: string}>}>
     */
    public function getLensMapProperty(): array
    {
        $map = [];

        foreach ($this->product->variants as $variant) {
            $usoValue = $variant->values->first(fn ($v) => $v->option->handle === 'uso');
            $lensValue = $variant->values->first(fn ($v) => $v->option->handle === 'tipo-de-lente');

            if (!$usoValue || !$lensValue) {
                continue;
            }

            $usoId = $usoValue->id;

            if (!isset($map[$usoId])) {
                $map[$usoId] = [
                    'uso_name'          => $usoValue->translate('name'),
                    'child_option_name' => $lensValue->option->translate('name'),
                    'values'            => [],
                ];
            }

            $map[$usoId]['values'][] = [
                'id'   => $lensValue->id,
                'name' => $lensValue->translate('name'),
            ];
        }

        return $map;
    }

    /**
     * Adds the first variant (solo marco) to the cart without lens selection.
     * Assumption: first variant = solo marco SKU — confirmed by product data.
     */
    public function addFrameOnly(): void
    {
        $variant = $this->product->variants->first();

        if ($variant->stock < 1) {
            $this->addError('lens', 'Stock insuficiente para este producto.');

            return;
        }

        CartSession::manager()->add($variant, 1);
        $this->dispatch('add-to-cart');
    }

    /**
     * Resolves the variant matching both uso and lens value IDs and adds it to cart.
     * Dispatches add-to-cart on success; sets a validation error on no match.
     */
    public function addWithLens(int $usoValueId, int $lensValueId): void
    {
        $variant = $this->product->variants->first(function ($v) use ($usoValueId, $lensValueId) {
            $ids = $v->values->pluck('id');

            return $ids->contains($usoValueId) && $ids->contains($lensValueId);
        });

        if (!$variant) {
            $this->addError('lens', 'No encontramos esa combinación de lente.');

            return;
        }

        if ($variant->stock < 1) {
            $this->addError('lens', 'Stock insuficiente.');

            return;
        }

        CartSession::manager()->add($variant, 1);
        $this->dispatch('add-to-cart');
    }

    public function render(): View
    {
        return view('livewire.product-page');
    }
}
