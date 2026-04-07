<?php

declare(strict_types=1);

namespace App\Traits;

use Lunar\Base\Purchasable;
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;

trait HandlesCart
{
    /**
     * Quick add a purchasable to the cart.
     */
    public function quickAdd(int $variantId, int $quantity = 1): void
    {
        $variant = ProductVariant::find($variantId);

        if (!$variant) {
            return;
        }

        if ($variant->stock < $quantity) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'No hay suficiente stock disponible.',
            ]);

            return;
        }

        CartSession::manager()->add($variant, $quantity);

        // Notify the cart component to update and show itself
        $this->dispatch('add-to-cart');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Producto añadido al carrito correctamente.',
        ]);
    }
}
