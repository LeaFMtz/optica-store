<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Traits\HandlesCart;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Product;

class CatalogPage extends Component
{
    use HandlesCart;
    use WithPagination;

    /**
     * Return the paginated products.
     */
    public function getProductsProperty()
    {
        return Product::query()
            ->with(['variants.basePrices', 'defaultUrl', 'thumbnail'])
            ->paginate(12);
    }

    public function render(): View
    {
        return view('livewire.catalog-page');
    }
}
