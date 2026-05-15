<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
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
    public function quote(string $postcode, string $city, string $state, int $weightGrams): array
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
                'declared_value' => 10000,
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
    public function createShipment(Order $order, string $serviceType): array
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
        $subtotal = $order->lines
            ->reject(fn ($line) => str_contains((string) ($line->identifier ?? ''), 'shipping'))
            ->sum('sub_total.value');

        $payload = [
            'service_type' => $serviceType,
            'account_id' => config('services.zipnova.account_id'),
            'destination' => [
                'name' => trim(($address->first_name ?? '').' '.($address->last_name ?? '')),
                'postcode' => $address->postcode ?? '',
                'city' => $address->city ?? '',
                'province' => $address->state ?? '',
                'address' => $address->line_one ?? '',
                'address_extra' => $address->line_two ?? '',
                'phone' => $address->contact_phone ?? '',
                'email' => $address->contact_email ?? '',
            ],
            'declared_value' => $subtotal,
            'package' => [
                'weight_grams' => (int) config('services.zipnova.default_package.weight_grams'),
                'height_cm' => (int) config('services.zipnova.default_package.height_cm'),
                'width_cm' => (int) config('services.zipnova.default_package.width_cm'),
                'length_cm' => (int) config('services.zipnova.default_package.length_cm'),
            ],
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

            return [
                'id' => (string) ($data['id'] ?? $shipmentId),
                'status' => (string) ($data['status'] ?? ''),
                'events' => (array) ($data['events'] ?? []),
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
     * Build an authenticated HTTP client for Zipnova API.
     */
    private function httpClient(): PendingRequest
    {
        $token = (string) config('services.zipnova.token', '');
        $secret = (string) config('services.zipnova.secret', '');
        $encoded = base64_encode($token.':'.$secret);

        return Http::withHeaders(['Authorization' => 'Basic '.$encoded])
            ->baseUrl((string) config('services.zipnova.base_url'))
            ->timeout(10)
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
     * @return array<int, array{identifier: string, name: string, price: int, currency: string, estimated_days: int}>
     */
    private function mapQuoteResults(array $data): array
    {
        \Log::info(print_r($data, true));
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
                'price' => (int) round((float) ($item['amounts']['price_incl_tax'] ?? 0)),
                'currency' => 'ARS',
                'estimated_days' => $days,
                'logistic_type' => (string) ($item['logistic_type'] ?? ''),
                'carrier_logo' => (string) ($item['carrier']['logo'] ?? ''),
            ];
        }, $results)));
    }
}
