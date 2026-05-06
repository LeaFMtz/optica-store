<?php

declare(strict_types=1);

namespace App\Lens;

use App\Models\ProductLensConfiguration;
use Illuminate\Support\Collection;
use Lunar\Base\Purchasable;
use Lunar\Models\Contracts\TaxClass;
use Lunar\Models\Price;
use Lunar\Models\TaxClass as TaxClassModel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LensPurchasable implements Purchasable
{
    public function __construct(private readonly ProductLensConfiguration $config) {}

    /**
     * Return a synthetic Price collection backed by the configuration's finalPrice.
     *
     * @return Collection<int, Price>
     */
    public function getPrices(): Collection
    {
        $price = new Price;
        $price->forceFill([
            'price' => $this->config->finalPrice(),
            'currency_id' => null,
            'min_quantity' => 1,
        ]);

        return collect([$price]);
    }

    public function getUnitQuantity(): int
    {
        return 1;
    }

    public function getTaxClass(): TaxClass
    {
        return TaxClassModel::getDefault();
    }

    public function getTaxReference(): ?string
    {
        return null;
    }

    /**
     * Lenses are not physical shippable items; treat as digital/service.
     */
    public function getType(): string
    {
        return 'digital';
    }

    /**
     * Human-readable description: "Uso · Tipo · Calidad".
     */
    public function getDescription(): string
    {
        return implode(' · ', [
            $this->config->lensUse->name,
            $this->config->lensType->name,
            $this->config->lensQuality->name,
        ]);
    }

    /**
     * Short option label — the quality name.
     */
    public function getOption(): ?string
    {
        return $this->config->lensQuality->name;
    }

    /**
     * Ordered list of option labels: use name then type name.
     *
     * @return Collection<int, string>
     */
    public function getOptions(): Collection
    {
        return collect([
            $this->config->lensUse->name,
            $this->config->lensType->name,
        ]);
    }

    /**
     * Stable identifier for this purchasable item.
     */
    public function getIdentifier(): string
    {
        return "lens-config-{$this->config->id}";
    }

    public function isShippable(): bool
    {
        return false;
    }

    /**
     * Lenses have no media thumbnail.
     */
    public function getThumbnail(): ?Media
    {
        return null;
    }

    public function canBeFulfilledAtQuantity(int $quantity): bool
    {
        return true;
    }

    public function getTotalInventory(): int
    {
        return 9999;
    }
}
