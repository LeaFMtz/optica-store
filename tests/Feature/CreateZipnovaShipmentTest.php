<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\CreateZipnovaShipment;
use App\Services\ZipnovaService;
use Tests\TestCase;

class CreateZipnovaShipmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.zipnova.mock' => true]);
    }

    public function test_zn_identifier_triggers_create_shipment_and_updates_meta(): void
    {
        $order = $this->buildMockOrder(['shipping_identifier' => 'ZN_OCA_STANDARD']);

        $zipnova = $this->createMock(ZipnovaService::class);
        $zipnova->expects($this->once())
            ->method('createShipment')
            ->with($order, 'OCA_STANDARD')
            ->willReturn([
                'id' => '789012',
                'label_code' => 'OCA-ABC123',
                'status' => 'new',
            ]);

        $job = new CreateZipnovaShipment($order);
        $job->handle($zipnova);

        $meta = (array) $order->meta;
        $this->assertSame('789012', $meta['zipnova_shipment_id']);
        $this->assertSame('OCA-ABC123', $meta['zipnova_label_code']);
        $this->assertSame('created', $meta['zipnova_status']);
    }

    public function test_retloc_identifier_skips_zipnova_and_does_not_modify_meta(): void
    {
        $order = $this->buildMockOrder(['shipping_identifier' => 'RETLOC']);

        $zipnova = $this->createMock(ZipnovaService::class);
        $zipnova->expects($this->never())->method('createShipment');

        $job = new CreateZipnovaShipment($order);
        $job->handle($zipnova);

        $meta = (array) $order->meta;
        $this->assertArrayNotHasKey('zipnova_shipment_id', $meta);
    }

    public function test_empty_identifier_skips_zipnova_and_does_not_modify_meta(): void
    {
        $order = $this->buildMockOrder([]);

        $zipnova = $this->createMock(ZipnovaService::class);
        $zipnova->expects($this->never())->method('createShipment');

        $job = new CreateZipnovaShipment($order);
        $job->handle($zipnova);

        $meta = (array) $order->meta;
        $this->assertArrayNotHasKey('zipnova_shipment_id', $meta);
    }

    public function test_failed_method_sets_zipnova_status_failed(): void
    {
        $order = $this->buildMockOrder(['shipping_identifier' => 'ZN_OCA_STANDARD']);

        $job = new CreateZipnovaShipment($order);
        $job->failed(new \RuntimeException('API error on every attempt'));

        $meta = (array) $order->meta;
        $this->assertSame('failed', $meta['zipnova_status']);
    }

    /**
     * Build a partial mock of Order that tracks meta mutations in memory.
     *
     * @param  array<string, mixed>  $initialMeta
     */
    private function buildMockOrder(array $initialMeta): \Lunar\Models\Order
    {
        /** @var \Lunar\Models\Order&\PHPUnit\Framework\MockObject\MockObject $order */
        $order = $this->getMockBuilder(\Lunar\Models\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save'])
            ->getMock();

        $order->meta = (object) $initialMeta;
        $order->expects($this->any())->method('save')->willReturn(true);

        return $order;
    }
}
