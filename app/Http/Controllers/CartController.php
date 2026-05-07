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

            $config = ProductLensConfiguration::with(['lensUse', 'lensType'])
                ->find($validated['lens_configuration_id']);

            if ($config) {
                $meta['lens_use_name'] = $config->lensUse?->name;
                $meta['lens_type_name'] = $config->lensType?->name;
            }
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
                'lens_label' => $this->buildLensLabel($meta),
                'prescription_rows' => $this->buildPrescriptionRows($meta),
            ];
        })->values()->all();
    }

    /**
     * Return "Uso · Tipo" string for a lens line, resolved from meta or DB fallback.
     *
     * @param  array<string, mixed>|null  $meta
     */
    private function buildLensLabel(?array $meta): ?string
    {
        if (empty($meta['lens_configuration_id'])) {
            return null;
        }

        $useName = $meta['lens_use_name'] ?? null;
        $typeName = $meta['lens_type_name'] ?? null;

        if (!$useName || !$typeName) {
            $config = ProductLensConfiguration::with(['lensUse', 'lensType'])
                ->find($meta['lens_configuration_id']);
            $useName ??= $config?->lensUse?->name;
            $typeName ??= $config?->lensType?->name;
        }

        $parts = array_filter([$useName, $typeName]);

        return $parts ? implode(' · ', $parts) : null;
    }

    /**
     * Return structured prescription rows for display.
     *
     * @param  array<string, mixed>|null  $meta
     * @return list<array{label: string, value: string}>
     */
    private function buildPrescriptionRows(?array $meta): array
    {
        $data = $meta['prescription_data'] ?? null;

        if (empty($data)) {
            return [];
        }

        $rows = [];

        foreach (['od' => 'OD', 'oi' => 'OI'] as $prefix => $label) {
            $parts = [];
            foreach ($data as $key => $value) {
                if (!str_starts_with($key, "{$prefix}_") || $value === null || $value === '') {
                    continue;
                }
                $fieldKey = substr($key, strlen("{$prefix}_"));
                $fieldLabel = ucfirst(substr($fieldKey, 0, 3));
                $formatted = is_numeric($value)
                    ? ((float) $value >= 0 ? "+{$value}" : (string) $value)
                    : (string) $value;
                $parts[] = "{$fieldLabel} {$formatted}";
            }
            if (!empty($parts)) {
                $rows[] = ['label' => $label, 'value' => implode('  ·  ', $parts)];
            }
        }

        if (isset($data['pd']) && $data['pd'] !== null && $data['pd'] !== '') {
            $rows[] = ['label' => 'DP', 'value' => (string) $data['pd']];
        } elseif (isset($data['pd_od']) || isset($data['pd_oi'])) {
            $rows[] = ['label' => 'DP', 'value' => ($data['pd_od'] ?? '—') . ' / ' . ($data['pd_oi'] ?? '—')];
        }

        return $rows;
    }
}
