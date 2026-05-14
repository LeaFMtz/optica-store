<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ShippingQuoteRequest;
use App\Services\ZipnovaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ShippingQuoteController extends Controller
{
    public function __construct(private readonly ZipnovaService $zipnova) {}

    /**
     * Return Zipnova shipping quote options for the given postcode and weight.
     *
     * Always returns 200. On API failure returns empty options array.
     */
    public function __invoke(ShippingQuoteRequest $request): JsonResponse
    {
        $postcode = (string) $request->validated('postcode');
        $city = (string) $request->validated('city');
        $state = (string) $request->validated('state');
        $weightGrams = max(10, (int) $request->validated('weight_grams', 300));

        $cacheKey = "zipnova_quote_{$postcode}_{$city}_{$state}_{$weightGrams}";

        try {
            $options = Cache::remember($cacheKey, 1800, function () use ($postcode, $city, $state, $weightGrams): array {
                return $this->zipnova->quote($postcode, $city, $state, $weightGrams);
            });

            return response()->json(['options' => $options]);
        } catch (RuntimeException) {
            return response()->json(['options' => []]);
        }
    }
}
