<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ZipnovaService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZipnovaServiceTest extends TestCase
{
    private ZipnovaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ZipnovaService();
    }

    public function test_quote_with_mock_returns_mapped_options(): void
    {
        config(['services.zipnova.mock' => true]);

        $options = $this->service->quote('1425', 500);

        $this->assertNotEmpty($options);
        $this->assertArrayHasKey('identifier', $options[0]);
        $this->assertArrayHasKey('name', $options[0]);
        $this->assertArrayHasKey('price', $options[0]);
        $this->assertArrayHasKey('currency', $options[0]);
        $this->assertArrayHasKey('estimated_days', $options[0]);

        foreach ($options as $option) {
            $this->assertStringStartsWith('ZN_', $option['identifier']);
            $this->assertIsString($option['name']);
            $this->assertIsInt($option['price']);
            $this->assertSame('ARS', $option['currency']);
            $this->assertIsInt($option['estimated_days']);
        }
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

        $this->service->quote('1425', 500);

        Http::assertSent(function (Request $request) use ($expectedEncoded): bool {
            return $request->hasHeader('Authorization', 'Basic ' . $expectedEncoded);
        });
    }

    /**
     * Create a minimal Order-like mock for testing.
     */
    private function createMockOrder(): \Lunar\Models\Order
    {
        $address = new \stdClass();
        $address->first_name = 'Test';
        $address->last_name = 'User';
        $address->postcode = '1425';
        $address->city = 'Buenos Aires';
        $address->state = 'Buenos Aires';
        $address->line_one = 'Av. Test 123';
        $address->line_two = '';
        $address->contact_phone = '';
        $address->contact_email = 'test@test.com';

        /** @var \Lunar\Models\Order $order */
        $order = $this->getMockBuilder(\Lunar\Models\Order::class)
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
