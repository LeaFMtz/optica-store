<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ShippingQuoteRequest;
use App\Services\PostalCodeService;
use App\Services\ZipnovaService;
use Illuminate\Http\JsonResponse;
use Lunar\Facades\CartSession;
use RuntimeException;

class ShippingQuoteController extends Controller
{
    public function __construct(
        private readonly ZipnovaService $zipnova,
        private readonly PostalCodeService $postalCode,
    ) {}

    /**
     * Return Zipnova shipping quote options for the given postcode and weight.
     *
     * Always returns 200. Returns empty options for unknown CP or API failure.
     */
    public function __invoke(ShippingQuoteRequest $request): JsonResponse
    {
        $postcode = (string) $request->validated('postcode');
        $weightGrams = max(10, (int) $request->validated('weight_grams', 300));

        $location = $this->postalCode->lookup($postcode);

        if ($location === null) {
            return response()->json(['options' => [], 'unknown_postcode' => true]);
        }

        $cart = CartSession::current();
        $declaredValue = $cart
            ? (int) round($cart->lines->sum('sub_total.value') / 100)
            : 0;
        $declaredValue = max($declaredValue, 2000);

        try {
            $options = $this->zipnova->quote($postcode, $location['city'], $location['state'], $weightGrams, $declaredValue);

            $sessionMap = collect($options)->keyBy('identifier')->all();
            session(['zipnova_quote_options' => array_merge(
                session('zipnova_quote_options', []),
                $sessionMap,
            )]);

            return response()->json(['options' => $options]);
        } catch (RuntimeException) {
            return response()->json(['options' => []]);
        }
    }
}
