<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductLensConfiguration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Lunar\Facades\CartSession;
use Lunar\Models\CartLine;
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
            'cart_total' => CartSession::current()?->total?->formatted(),
        ]);
    }

    /**
     * Add a purchasable (ProductVariant) to the cart.
     *
     * Optionally accepts `parent_line_id` to anchor a lens line to a frame line.
     * The parent_line_id is stored in the cart line's meta field.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', Rule::exists(ProductVariant::class, 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'parent_line_id' => ['nullable', 'integer', Rule::exists(CartLine::class, 'id')],
            'lens_configuration_id' => ['nullable', 'integer', Rule::exists(ProductLensConfiguration::class, 'id')],
            'combo_id' => ['nullable', 'string', 'max:64'],
            'prescription_data' => ['nullable', 'array'],
            'prescription_data.*' => ['nullable', 'numeric'],
        ]);

        /** @var ProductVariant $variant */
        $variant = ProductVariant::findOrFail($validated['variant_id']);

        $alreadyInCart = CartSession::current()?->lines
            ->where('purchasable_type', 'product_variant')
            ->where('purchasable_id', $variant->id)
            ->sum('quantity') ?? 0;

        if (! $variant->canBeFulfilledAtQuantity($alreadyInCart + $validated['quantity'])) {
            return response()->json([
                'message' => 'La cantidad supera el stock disponible.',
            ], 422);
        }

        $meta = [];

        if (!empty($validated['parent_line_id'])) {
            $meta['parent_line_id'] = $validated['parent_line_id'];
        }

        if (!empty($validated['lens_configuration_id'])) {
            $meta['lens_configuration_id'] = $validated['lens_configuration_id'];
        }

        if (!empty($validated['combo_id'])) {
            $meta['combo_id'] = $validated['combo_id'];
        }

        if (!empty($validated['prescription_data'])) {
            $meta['prescription_data'] = $validated['prescription_data'];
        }

        CartSession::manager()->add($variant, (int) $validated['quantity'], $meta);

        $cart = CartSession::current();

        // When a combo_id is present we can find the exact line — avoids race conditions.
        $newLineId = !empty($meta['combo_id'])
            ? CartLine::where('cart_id', $cart?->id)
                ->whereJsonContains('meta->combo_id', $meta['combo_id'])
                ->value('id')
            : CartLine::where('cart_id', $cart?->id)->orderByDesc('id')->value('id');

        return response()->json([
            'lines' => $this->serializeLines(),
            'count' => $cart?->lines?->sum('quantity') ?? 0,
            'cart_total' => $cart?->total?->formatted(),
            'new_line_id' => $newLineId,
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

        $newQty = (int) $validated['quantity'];
        $line = CartLine::findOrFail($id);

        if ($newQty > $line->quantity && $line->purchasable_type === 'product_variant') {
            $variant = ProductVariant::find($line->purchasable_id);

            if ($variant) {
                $otherLinesQty = CartSession::current()?->lines
                    ->where('purchasable_type', 'product_variant')
                    ->where('purchasable_id', $variant->id)
                    ->where('id', '!=', $id)
                    ->sum('quantity') ?? 0;

                if (! $variant->canBeFulfilledAtQuantity($otherLinesQty + $newQty)) {
                    return response()->json([
                        'message' => 'La cantidad supera el stock disponible.',
                    ], 422);
                }
            }
        }

        CartSession::updateLines(collect([[
            'id' => $id,
            'quantity' => $newQty,
        ]]));

        return response()->json([
            'lines' => $this->serializeLines(),
            'count' => CartSession::current()?->lines?->sum('quantity') ?? 0,
            'cart_total' => CartSession::current()?->total?->formatted(),
        ]);
    }

    /**
     * Remove a line from the cart.
     *
     * Also cascade-deletes any child lines (lines whose meta->parent_line_id = $id).
     */
    public function destroy(int $id): JsonResponse
    {
        DB::transaction(function () use ($id) {
            CartLine::whereJsonContains('meta->parent_line_id', $id)->delete();
            CartSession::remove($id);
        });

        return response()->json([
            'lines' => $this->serializeLines(),
            'count' => CartSession::current()?->lines?->sum('quantity') ?? 0,
            'cart_total' => CartSession::current()?->total?->formatted(),
        ]);
    }

    /**
     * Serialize the current cart lines to a plain array.
     *
     * @return list<array{id: int, variant_id: int, identifier: string, quantity: int, description: string, thumbnail: string|null, option: string|null, options: string, sub_total: string, unit_price: string, meta: array<string, mixed>|null, prescription_summary: string|null}>
     */
    private function serializeLines(): array
    {
        $cart = CartSession::current();

        if (!$cart) {
            return [];
        }

        return $cart->lines->map(function ($line) {
            $meta = $line->meta ? (array) $line->meta : null;

            return [
                'id' => $line->id,
                'variant_id' => $line->purchasable_id,
                'identifier' => $line->purchasable->getIdentifier(),
                'quantity' => $line->quantity,
                'description' => $line->purchasable->getDescription(),
                'thumbnail' => $line->purchasable->getThumbnail()?->getUrl(),
                'option' => $line->purchasable->getOption(),
                'options' => $line->purchasable->getOptions()->implode(' / '),
                'sub_total' => $line->subTotal->formatted(),
                'unit_price' => $line->unitPrice->formatted(),
                'meta' => $meta,
                'prescription_summary' => $this->buildPrescriptionSummary($meta),
            ];
        })->values()->all();
    }

    /**
     * Build a human-readable prescription summary from meta.prescription_data.
     *
     * Iterates dynamically over whatever field keys exist — no hardcoded field names.
     *
     * @param  array<string, mixed>|null  $meta
     */
    private function buildPrescriptionSummary(?array $meta): ?string
    {
        $data = $meta['prescription_data'] ?? null;

        if (empty($data)) {
            return null;
        }

        $parts = [];

        foreach (['od' => 'OD', 'oi' => 'OI'] as $prefix => $label) {
            $eyeParts = [];
            foreach ($data as $key => $value) {
                if (!str_starts_with($key, "{$prefix}_")) {
                    continue;
                }
                if ($value === null || $value === '') {
                    continue;
                }
                $fieldKey = substr($key, strlen("{$prefix}_"));
                $fieldLabel = ucfirst(substr($fieldKey, 0, 3));
                if (is_numeric($value)) {
                    $formatted = (float) $value >= 0 ? "+{$value}" : (string) $value;
                } else {
                    $formatted = (string) $value;
                }
                $eyeParts[] = "{$fieldLabel} {$formatted}";
            }
            if (!empty($eyeParts)) {
                $parts[] = "{$label}: " . implode(' · ', $eyeParts);
            }
        }

        if (isset($data['pd']) && $data['pd'] !== null && $data['pd'] !== '') {
            $parts[] = "DP: {$data['pd']}";
        } elseif (isset($data['pd_od']) || isset($data['pd_oi'])) {
            $parts[] = "DP: " . ($data['pd_od'] ?? '—') . '/' . ($data['pd_oi'] ?? '—');
        }

        return !empty($parts) ? implode("\n", $parts) : null;
    }
}
