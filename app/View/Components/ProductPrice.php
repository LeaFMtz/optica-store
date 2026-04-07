<?php

declare(strict_types=1);

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Lunar\Facades\Pricing;
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;

class ProductPrice extends Component
{
    public ?Price $price = null;

    public ?Price $basePrice = null;

    public ?ProductVariant $variant = null;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($product = null, $variant = null)
    {
        $pricing = Pricing::for(
            $variant ?: $product->variants->first(),
        )->get();

        $this->price = $pricing->matched;
        $this->basePrice = $pricing->base;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.product-price');
    }
}
