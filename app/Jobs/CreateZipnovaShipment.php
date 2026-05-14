<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\ZipnovaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Lunar\Models\Order;

class CreateZipnovaShipment implements ShouldQueue
{
    use Queueable;

    /** @var int Maximum number of attempts. */
    public int $tries = 3;

    /** @var int Seconds to wait before retrying. */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order) {}

    /**
     * Execute the job.
     */
    public function handle(ZipnovaService $zipnova): void
    {
        $meta = (array) ($this->order->meta ?? []);
        $identifier = (string) ($meta['shipping_identifier'] ?? '');

        if (!str_starts_with($identifier, 'ZN_')) {
            return;
        }

        $serviceType = substr($identifier, 3);

        $result = $zipnova->createShipment($this->order, $serviceType);

        $meta['zipnova_shipment_id'] = $result['id'];
        $meta['zipnova_label_code'] = $result['label_code'];
        $meta['zipnova_status'] = 'created';

        $this->order->meta = $meta;
        $this->order->save();
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $meta = (array) ($this->order->meta ?? []);
        $meta['zipnova_status'] = 'failed';

        $this->order->meta = $meta;
        $this->order->save();
    }
}
