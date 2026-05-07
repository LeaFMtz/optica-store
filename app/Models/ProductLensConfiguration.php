<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;

class ProductLensConfiguration extends Model
{
    protected $table = 'opt_product_lens_configurations';

    protected $fillable = [
        'product_id',
        'lens_use_id',
        'lens_type_id',
        'crystal_product_id',
        'price_override',
    ];

    /**
     * @return BelongsTo<Product, ProductLensConfiguration>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<LensUse, ProductLensConfiguration>
     */
    public function lensUse(): BelongsTo
    {
        return $this->belongsTo(LensUse::class);
    }

    /**
     * @return BelongsTo<LensType, ProductLensConfiguration>
     */
    public function lensType(): BelongsTo
    {
        return $this->belongsTo(LensType::class);
    }

    /**
     * @return BelongsTo<Product, ProductLensConfiguration>
     */
    public function crystalProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'crystal_product_id');
    }

    /**
     * Effective price in centavos.
     * Uses price_override when set, otherwise falls back to 0 (price managed in Lunar).
     */
    public function finalPrice(): int
    {
        return $this->price_override ?? 0;
    }
}
