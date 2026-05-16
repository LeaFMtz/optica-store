<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
use Lunar\Models\ProductVariant;
use RuntimeException;

/**
 * Zipnova Shipping API service using Laravel Http facade.
 *
 * Supports mock mode via ZIPNOVA_MOCK=true which returns fixture data
 * without making any real HTTP calls.
 */
class ZipnovaService
{
    /**
     * Get shipping quote options for a given postcode and weight.
     *
     * @return array<int, array{identifier: string, name: string, price: int, currency: string, estimated_days: int}>
     *
     * @throws RuntimeException on HTTP failure
     */
    public function quote(string $postcode, string $city, string $state, int $weightGrams, int $declaredValue = 2000): array
    {
        if ($this->isMock()) {
            return $this->mapQuoteResults($this->fixture('quote'));
        }

        $package = config('services.zipnova.default_package');

        try {
            $shipData = [
                'account_id' => (int) config('services.zipnova.account_id'),
                'destination' => [
                    'zipcode' => $postcode,
                    'city' => $city,
                    'state' => $state,
                ],
                'items' => [
                    [
                        'weight' => max(10, $weightGrams),
                        'height' => (int) $package['height_cm'],
                        'width' => (int) $package['width_cm'],
                        'length' => (int) $package['length_cm'],
                        'description' => 'Producto',
                    ],
                ],
                'declared_value' => $declaredValue,
                'type_packaging' => 'dynamic',
                'sort_by' => 'price',
            ];

            $response = $this->httpClient()
                ->post('/v2/shipments/quote', $shipData);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Zipnova API error: '.$response->status().' - '.$response->body(),
                    $response->status(),
                );
            }

            return $this->mapQuoteResults($response->json());
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Zipnova connection error: '.$e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Create a shipment in Zipnova after order payment.
     *
     * @return array{id: string, label_code: string, status: string}
     *
     * @throws RuntimeException on HTTP failure
     */
    public function createShipment(Order $order, string $serviceType, ?int $pointId = null): array
    {
        if ($this->isMock()) {
            $fixture = $this->fixture('create');

            return [
                'id' => (string) ($fixture['id'] ?? ''),
                'label_code' => (string) ($fixture['packages'][0]['label_code'] ?? ''),
                'status' => (string) ($fixture['status'] ?? ''),
            ];
        }

        $address = $order->shippingAddress;
        $addressMeta = (array) ($address->meta ?? []);
        $subtotal = $order->lines
            ->reject(fn ($line) => str_contains((string) ($line->identifier ?? ''), 'shipping'))
            ->sum('sub_total.value');

        // Calculate actual package weight from order lines (same as quote uses from cart)
        $totalWeight = $order->lines->sum(function ($line) {
            if ($line->purchasable_type === 'product_variant') {
                $variant = ProductVariant::find($line->purchasable_id);

                return $variant ? (int) ($variant->weight_value ?? 0) : 0;
            }

            return 0;
        });
        $weight = $totalWeight > 0 ? max(10, $totalWeight) : (int) config('services.zipnova.default_package.weight_grams');

        $lineOne = $address->line_one ?? '';
        [$street, $streetNumber] = $this->parseStreetAndNumber($lineOne);

        // Zipnova requires street_number as a non-empty string
        if ($streetNumber === '' || $streetNumber === '0') {
            $streetNumber = 'S/N';
        }

        $state = $address->state ?? '';
        // Zipnova requires state as a non-empty string when city is present
        if ($state === '') {
            $state = '-';
        }

        $payload = [
            'external_id' => 'OPT-'.$order->id,
            'service_type' => $serviceType,
            'account_id' => config('services.zipnova.account_id'),
            'origin_id' => config('services.zipnova.origin_id'),
            'destination' => [
                'name' => trim(($address->first_name ?? '').' '.($address->last_name ?? '')),
                'zipcode' => $address->postcode ?? '',
                'city' => $address->city ?? '',
                'state' => $state,
                'street' => $street,
                'street_number' => $streetNumber,
                'street_extras' => $address->line_two ?? '',
                'phone' => $address->contact_phone ?? '',
                'email' => $address->contact_email ?? '',
                'document' => $addressMeta['dni'] ?? $address->tax_identifier ?? '',
                ...(($pointId !== null) ? ['point_id' => $pointId] : []),
            ],
            'declared_value' => (int) round($subtotal / 100),
            'packages' => [[
                'description_1' => 'Producto',
                'weight' => $weight,
                'height' => (int) config('services.zipnova.default_package.height_cm'),
                'width' => (int) config('services.zipnova.default_package.width_cm'),
                'length' => (int) config('services.zipnova.default_package.length_cm'),
                'classification_id' => (string) config('services.zipnova.default_package.classification_id', '1'),
            ]],
            ...(($pointId !== null) ? ['pickup_point' => ['point_id' => $pointId]] : []),
        ];

        try {
            $response = $this->httpClient()->post('/v2/shipments', $payload);

            if ($response->failed()) {
                throw new RuntimeException(
                    'Zipnova API error creating shipment: '.$response->status().' - '.$response->body(),
                    $response->status(),
                );
            }

            $data = $response->json();

            return [
                'id' => (string) ($data['id'] ?? ''),
                'label_code' => (string) ($data['packages'][0]['label_code'] ?? ''),
                'status' => (string) ($data['status'] ?? ''),
            ];
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Zipnova connection error: '.$e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Cancel an existing shipment.
     *
     * @return array{shipment_id: string, success: bool, result: string}
     *
     * @throws RuntimeException on HTTP failure
     */
    public function cancelShipment(string $shipmentId): array
    {
        if ($this->isMock()) {
            $fixture = $this->fixture('cancel');

            return [
                'shipment_id' => (string) ($fixture['shipment_id'] ?? $shipmentId),
                'success' => (bool) ($fixture['success'] ?? true),
                'result' => (string) ($fixture['result'] ?? ''),
            ];
        }

        try {
            $response = $this->httpClient()->post("/v2/shipments/{$shipmentId}/cancel");

            if ($response->failed()) {
                throw new RuntimeException(
                    'Zipnova API error cancelling shipment: '.$response->status().' - '.$response->body(),
                    $response->status(),
                );
            }

            $data = $response->json();

            return [
                'shipment_id' => (string) ($data['shipment_id'] ?? $shipmentId),
                'success' => (bool) ($data['success'] ?? false),
                'result' => (string) ($data['result'] ?? ''),
            ];
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Zipnova connection error: '.$e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Get tracking events for a shipment.
     *
     * @return array{id: string, status: string, events: array<int, array{date: string, description: string, location: string}>}
     *
     * @throws RuntimeException on HTTP failure
     */
    public function getTracking(string $shipmentId): array
    {
        if ($this->isMock()) {
            $fixture = $this->fixture('tracking');

            return [
                'id' => (string) ($fixture['id'] ?? $shipmentId),
                'status' => (string) ($fixture['status'] ?? ''),
                'events' => (array) ($fixture['events'] ?? []),
            ];
        }

        try {
            $response = $this->httpClient()->get("/v2/shipments/{$shipmentId}/tracking");

            if ($response->failed()) {
                throw new RuntimeException(
                    'Zipnova API error fetching tracking: '.$response->status().' - '.$response->body(),
                    $response->status(),
                );
            }

            $data = $response->json();

            // Zipnova returns a flat array of events, not { events: [...] }
            $rawEvents = is_array($data) ? $data : [];
            $events = array_map(function (array $item): array {
                $date = !empty($item['occurred_at'])
                    ? Carbon::parse($item['occurred_at'])->setTimezone('America/Argentina/Buenos_Aires')->format('d/m/Y H:i')
                    : '';

                return [
                    'date' => $date,
                    'description' => $item['status']['visible_name'] ?? $item['status']['name'] ?? '',
                    'location' => $item['status']['substatus'] ?? '',
                    'code' => $item['status']['code'] ?? '',
                ];
            }, $rawEvents);

            $lastEvent = end($events);

            return [
                'id' => $shipmentId,
                'status' => $lastEvent['code'] ?? 'unknown',
                'events' => $events,
            ];
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Zipnova connection error: '.$e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Extract the service type code from a Zipnova shipping identifier.
     *
     * Format: ZN_{carrierId}_{serviceTypeCode}
     * Example: ZN_233_pickup_point → pickup_point
     */
    public function extractServiceType(string $identifier): string
    {
        $parts = explode('_', $identifier);

        if (count($parts) < 3 || $parts[0] !== 'ZN') {
            return '';
        }

        return implode('_', array_slice($parts, 2));
    }

    /**
     * Build an authenticated HTTP client for Zipnova API.
     */
    private function httpClient(): PendingRequest
    {
        $token = (string) config('services.zipnova.token', '');
        $secret = (string) config('services.zipnova.secret', '');
        $encoded = base64_encode($token.':'.$secret);

        return Http::withHeaders(['Authorization' => 'Basic '.$encoded])
            ->baseUrl((string) config('services.zipnova.base_url'))
            ->timeout(30)
            ->acceptJson();
    }

    /**
     * Check if mock mode is active.
     */
    private function isMock(): bool
    {
        return (bool) config('services.zipnova.mock', false);
    }

    /**
     * Load a fixture JSON file by method name.
     *
     * @return array<string, mixed>
     */
    private function fixture(string $method): array
    {
        $map = [
            'quote' => 'zipnova_quote_response',
            'create' => 'zipnova_create_response',
            'cancel' => 'zipnova_cancel_response',
            'tracking' => 'zipnova_tracking_response',
        ];

        $filename = $map[$method] ?? $method;
        $path = base_path("tests/Fixtures/{$filename}.json");

        if (!file_exists($path)) {
            throw new RuntimeException("Zipnova fixture file not found: {$path}");
        }

        return json_decode(file_get_contents($path), true) ?? [];
    }

    /**
     * Map raw Zipnova quote response to the normalized options array.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, array{
     *   identifier: string,
     *   name: string,
     *   price: int,
     *   currency: string,
     *   estimated_days: string,
     *   logistic_type: string,
     *   carrier_logo: string,
     *   service_type_code: string,
     *   pickup_points: array<int, array{point_id: int, description: string, location: array<string, mixed>}>,
     * }>
     */
    private function mapQuoteResults(array $data): array
    {
        $results = $data['results'] ?? [];

        return array_values(array_filter(array_map(function (array $item): ?array {
            if (!($item['selectable'] ?? false)) {
                return null;
            }

            $carrierId = (int) ($item['carrier']['id'] ?? 0);
            $serviceCode = (string) ($item['service_type']['code'] ?? '');
            $min = (int) ($item['delivery_time']['min'] ?? 0);
            $max = (int) ($item['delivery_time']['max'] ?? 0);
            $days = $min === $max ? "{$min} días" : "{$min}–{$max} días";

            return [
                'identifier' => "ZN_{$carrierId}_{$serviceCode}",
                'name' => ($item['carrier']['name'] ?? '').' — '.($item['service_type']['name'] ?? ''),
                'price' => (int) round((float) ($item['amounts']['price_incl_tax'] ?? 0) * 100 * 1.21),
                'currency' => 'ARS',
                'estimated_days' => $days,
                'logistic_type' => (string) ($item['logistic_type'] ?? ''),
                'carrier_logo' => (string) ($item['carrier']['logo'] ?? ''),
                'service_type_code' => $serviceCode,
                'pickup_points' => array_values(array_map(function (array $p): array {
                    return [
                        'point_id' => (int) $p['point_id'],
                        'description' => (string) ($p['description'] ?? ''),
                        'location' => $p['location'] ?? [],
                    ];
                }, (array) ($item['pickup_points'] ?? []))),
            ];
        }, $results)));
    }

    /**
     * Parse street name and number from an address line like "SAN MARTIN 333".
     *
     * @return array{0: string, 1: string}
     */
    private function parseStreetAndNumber(string $line): array
    {
        $parts = explode(' ', trim($line));
        $last = end($parts);

        if (is_numeric($last) && count($parts) > 1) {
            array_pop($parts);

            return [implode(' ', $parts), $last];
        }

        return [$line, ''];
    }
}
