<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Traits\HandlesCart;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Lunar\Models\Product;

class SearchPage extends Component
{
    use HandlesCart;
    use WithPagination;

    /**
     * The search term.
     */
    public ?string $term = null;

    /**
     * {@inheritDoc}
     */
    protected $queryString = [
        'term',
    ];

    /**
     * Return the search results.
     */
    public function getResultsProperty(): LengthAwarePaginator
    {
        return Product::search($this->term)->paginate(50);
    }

    public function render(): View
    {
        return view('livewire.search-page');
    }
}
