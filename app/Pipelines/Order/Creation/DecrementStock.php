<?php

declare(strict_types=1);

namespace App\Pipelines\Order\Creation;

use Closure;
use Lunar\Models\Contracts\Order as OrderContract;
use Lunar\Models\ProductVariant;

class DecrementStock
{
    public function handle(OrderContract $order, Closure $next): mixed
    {
        $order->lines
            ->where('purchasable_type', 'product_variant')
            ->each(function ($line) {
                /** @var ProductVariant $variant */
                $variant = ProductVariant::lockForUpdate()->find($line->purchasable_id);

                if (!$variant) {
                    return;
                }

                if (!$variant->canBeFulfilledAtQuantity($line->quantity)) {
                    throw new \RuntimeException(
                        "Stock insuficiente para la variante {$variant->id} al crear la orden.",
                    );
                }

                match ($variant->purchasable) {
                    'always' => null,
                    'in_stock' => $variant->decrement('stock', $line->quantity),
                    'in_stock_or_on_backorder' => $this->decrementBackorder($variant, $line->quantity),
                    default => null,
                };
            });

        return $next($order);
    }

    private function decrementBackorder(ProductVariant $variant, int $quantity): void
    {
        $fromStock = min($variant->stock, $quantity);
        $fromBackorder = $quantity - $fromStock;

        if ($fromStock > 0) {
            $variant->decrement('stock', $fromStock);
        }

        if ($fromBackorder > 0) {
            $variant->decrement('backorder', $fromBackorder);
        }
    }
}
