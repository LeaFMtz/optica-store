<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;

class CartController extends Controller
{
    /**
     * Return the current cart lines serialized as a plain array.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'lines' => $this->serializeLines(),
            'count' => CartSession::current()?->lines?->sum('quantity') ?? 0,
        ]);
    }

    /**
     * Add a purchasable (ProductVariant) to the cart.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', Rule::exists(ProductVariant::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        /** @var ProductVariant $variant */
        $variant = ProductVariant::findOrFail($validated['variant_id']);

        if ($variant->stock < $validated['quantity']) {
            return response()->json([
                'message' => 'The quantity exceeds the available stock.',
            ], 422);
        }

        CartSession::manager()->add($variant, (int) $validated['quantity']);

        return response()->json([
            'lines' => $this->serializeLines(),
            'count' => CartSession::current()?->lines?->sum('quantity') ?? 0,
        ]);
    }

    /**
     * Update the quantity of a cart line.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        CartSession::updateLines(collect([[
            'id' => $id,
            'quantity' => (int) $validated['quantity'],
        ]]));

        return response()->json([
            'lines' => $this->serializeLines(),
            'count' => CartSession::current()?->lines?->sum('quantity') ?? 0,
        ]);
    }

    /**
     * Remove a line from the cart.
     */
    public function destroy(int $id): JsonResponse
    {
        CartSession::remove($id);

        return response()->json([
            'lines' => $this->serializeLines(),
            'count' => CartSession::current()?->lines?->sum('quantity') ?? 0,
        ]);
    }

    /**
     * Serialize the current cart lines to a plain array.
     *
     * @return list<array{id: int, identifier: string, quantity: int, description: string, thumbnail: string|null, option: string|null, options: string, sub_total: string, unit_price: string}>
     */
    private function serializeLines(): array
    {
        $cart = CartSession::current();

        if (!$cart) {
            return [];
        }

        return $cart->lines->map(function ($line) {
            return [
                'id' => $line->id,
                'identifier' => $line->purchasable->getIdentifier(),
                'quantity' => $line->quantity,
                'description' => $line->purchasable->getDescription(),
                'thumbnail' => $line->purchasable->getThumbnail()?->getUrl(),
                'option' => $line->purchasable->getOption(),
                'options' => $line->purchasable->getOptions()->implode(' / '),
                'sub_total' => $line->subTotal->formatted(),
                'unit_price' => $line->unitPrice->formatted(),
            ];
        })->values()->all();
    }
}
