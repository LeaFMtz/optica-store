<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ZipnovaService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lunar\Models\Order;
use Tests\TestCase;

class ZipnovaServiceTest extends TestCase
{
    private ZipnovaService $service;

    public function test_quote_with_mock_returns_mapped_options(): void
    {
        config(['services.zipnova.mock' => true]);

        $options = $this->service->quote('1425', 'Buenos Aires', 'Buenos Aires', 500);

        $this->assertNotEmpty($options);
        $this->assertArrayHasKey('identifier', $options[0]);
        $this->assertArrayHasKey('name', $options[0]);
        $this->assertArrayHasKey('price', $options[0]);
        $this->assertArrayHasKey('currency', $options[0]);
        $this->assertArrayHasKey('estimated_days', $options[0]);
        $this->assertArrayHasKey('service_type_code', $options[0]);
        $this->assertArrayHasKey('pickup_points', $options[0]);

        foreach ($options as $option) {
            $this->assertStringStartsWith('ZN_', $option['identifier']);
            $this->assertIsString($option['name']);
            $this->assertIsInt($option['price']);
            $this->assertSame('ARS', $option['currency']);
            $this->assertIsString($option['estimated_days']);
            $this->assertIsString($option['service_type_code']);
            $this->assertIsArray($option['pickup_points']);
        }
    }

    public function test_quote_maps_service_type_code_and_pickup_points(): void
    {
        config(['services.zipnova.mock' => true]);

        $options = $this->service->quote('1425', 'Buenos Aires', 'Buenos Aires', 500);

        $pickupOption = collect($options)->firstWhere('service_type_code', 'pickup_point');
        $this->assertNotNull($pickupOption, 'Expected a pickup_point option from the fixture');
        $this->assertNotEmpty($pickupOption['pickup_points']);
        $this->assertArrayHasKey('point_id', $pickupOption['pickup_points'][0]);
        $this->assertArrayHasKey('description', $pickupOption['pickup_points'][0]);
        $this->assertArrayHasKey('location', $pickupOption['pickup_points'][0]);
        $this->assertIsInt($pickupOption['pickup_points'][0]['point_id']);

        $standardOption = collect($options)->firstWhere('service_type_code', 'standard_delivery');
        $this->assertNotNull($standardOption, 'Expected a standard_delivery option from the fixture');
        $this->assertSame([], $standardOption['pickup_points']);
    }

    public function test_create_shipment_with_mock_returns_expected_shape(): void
    {
        config(['services.zipnova.mock' => true]);

        $order = $this->createMockOrder();

        $result = $this->service->createShipment($order, 'OCA_STANDARD');

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('label_code', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertSame('789012', $result['id']);
        $this->assertSame('OCA-ABC123', $result['label_code']);
    }

    public function test_cancel_shipment_with_mock_returns_expected_shape(): void
    {
        config(['services.zipnova.mock' => true]);

        $result = $this->service->cancelShipment('789012');

        $this->assertArrayHasKey('shipment_id', $result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('result', $result);
        $this->assertTrue($result['success']);
    }

    public function test_get_tracking_with_mock_returns_expected_shape(): void
    {
        config(['services.zipnova.mock' => true]);

        $result = $this->service->getTracking('789012');

        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('events', $result);
        $this->assertIsArray($result['events']);
        $this->assertNotEmpty($result['events']);
    }

    public function test_quote_sends_basic_auth_header_when_not_mock(): void
    {
        config(['services.zipnova.mock' => false]);
        config(['services.zipnova.token' => 'mytoken']);
        config(['services.zipnova.secret' => 'mysecret']);
        config(['services.zipnova.base_url' => 'https://api.zipnova.com.ar']);

        $expectedEncoded = base64_encode('mytoken:mysecret');

        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'all_results' => [],
            ], 200),
        ]);

        $this->service->quote('1425', 'Buenos Aires', 'Buenos Aires', 500);

        Http::assertSent(function (Request $request) use ($expectedEncoded): bool {
            return $request->hasHeader('Authorization', 'Basic '.$expectedEncoded);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ZipnovaService;
    }

    /**
     * Create a minimal Order-like mock for testing.
     */
    private function createMockOrder(): Order
    {
        $address = new \stdClass;
        $address->first_name = 'Test';
        $address->last_name = 'User';
        $address->postcode = '1425';
        $address->city = 'Buenos Aires';
        $address->state = 'Buenos Aires';
        $address->line_one = 'Av. Test 123';
        $address->line_two = '';
        $address->contact_phone = '';
        $address->contact_email = 'test@test.com';

        /** @var Order $order */
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $order->expects($this->any())
            ->method('__get')
            ->willReturnCallback(function (string $property) use ($address): mixed {
                return match ($property) {
                    'lines' => collect([]),
                    'shippingAddress' => $address,
                    default => null,
                };
            });

        return $order;
    }
}
