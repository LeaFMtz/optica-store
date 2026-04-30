<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Banner;
use App\Traits\HandlesCart;
use Illuminate\View\View;
use Livewire\Component;
use Lunar\Models\Collection;
use Lunar\Models\Url;

class Home extends Component
{
    use HandlesCart;

    /**
     * Return active banners for hero section.
     */
    public function getHeroBannersProperty()
    {
        return Banner::where('is_active', true)
            ->where('position', 'home_hero')
            ->orderBy('order')
            ->get();
    }

    /**
     * Return active banners for middle section.
     */
    public function getMiddleBannersProperty()
    {
        return Banner::where('is_active', true)
            ->where('position', 'home_middle')
            ->orderBy('order')
            ->get();
    }

    /**
     * Return active banners for bottom section.
     */
    public function getBottomBannersProperty()
    {
        return Banner::where('is_active', true)
            ->where('position', 'home_bottom')
            ->orderBy('order')
            ->get();
    }

    /**
     * Return the sale collection.
     */
    public function getSaleCollectionProperty(): ?Collection
    {
        return Url::whereElementType((new Collection)->getMorphClass())->whereSlug('sale')->first()?->element ?? null;
    }

    /**
     * Return all products in sale collection.
     */
    public function getSaleProductsProperty()
    {
        if (! $this->getSaleCollectionProperty()) {
            return collect();
        }

        return $this->getSaleCollectionProperty()
            ->products()
            ->with(['thumbnail', 'variants', 'variants.prices', 'defaultUrl'])
            ->get();
    }

    /**
     * Return a random collection.
     */
    public function getRandomCollectionProperty(): ?Collection
    {
        $collections = Url::whereElementType((new Collection)->getMorphClass());

        if ($this->getSaleCollectionProperty()) {
            $collections = $collections->where('element_id', '!=', $this->getSaleCollectionProperty()?->id);
        }

        return $collections->inRandomOrder()->first()?->element;
    }

    public function render(): View
    {
        return view('livewire.home');
    }
}
