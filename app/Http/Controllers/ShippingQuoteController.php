<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ShippingQuoteRequest;
use App\Services\PostalCodeService;
use App\Services\ZipnovaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
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

        $cacheKey = "zipnova_quote_{$postcode}_{$weightGrams}";

        try {
            $options = Cache::remember($cacheKey, 1800, function () use ($postcode, $location, $weightGrams): array {
                return $this->zipnova->quote($postcode, $location['city'], $location['state'], $weightGrams);
            });

            return response()->json(['options' => $options]);
        } catch (RuntimeException) {
            return response()->json(['options' => []]);
        }
    }
}
