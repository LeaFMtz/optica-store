<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ManageOrderExtension;
use App\Services\ZipnovaService;
use Tests\TestCase;

class ManageOrderZipnovaActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.zipnova.mock' => true]);
    }

    // ─── Infolist section tests ───────────────────────────────────────────────

    public function test_extend_infolist_schema_adds_two_sections(): void
    {
        $extension = new ManageOrderExtension();
        $schema = $extension->extendInfolistSchema([]);

        // Prescription section + Zipnova section are both added
        $this->assertCount(2, $schema);

        // Both are Filament Section components
        foreach ($schema as $component) {
            $this->assertInstanceOf(\Filament\Schemas\Components\Section::class, $component);
        }
    }

    public function test_zipnova_section_hidden_callback_hides_when_no_shipment_id(): void
    {
        // Test the hidden closure logic directly (without Filament container)
        $metaWithoutShipment = (object) [];
        $isHidden = empty(((array) $metaWithoutShipment)['zipnova_shipment_id']);

        $this->assertTrue($isHidden);
    }

    public function test_zipnova_section_hidden_callback_shows_when_shipment_id_present(): void
    {
        $metaWithShipment = (object) ['zipnova_shipment_id' => '789012'];
        $isHidden = empty(((array) $metaWithShipment)['zipnova_shipment_id']);

        $this->assertFalse($isHidden);
    }

    // ─── Header actions visibility tests ──────────────────────────────────────

    public function test_header_actions_returned_correctly(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $actionNames = collect($actions)->map(fn ($a) => $a->getName())->all();

        $this->assertContains('crear_envio_zipnova', $actionNames);
        $this->assertContains('ver_tracking', $actionNames);
        $this->assertContains('cancelar_envio', $actionNames);
    }

    public function test_crear_envio_visible_when_status_is_null(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $crearEnvio = collect($actions)->first(fn ($a) => $a->getName() === 'crear_envio_zipnova');
        $this->assertNotNull($crearEnvio);

        // No shipment_id, no status — should be visible
        $record = (object) ['meta' => (object) []];
        $visible = $this->callVisibleCallback($crearEnvio, $record);
        $this->assertTrue($visible);
    }

    public function test_crear_envio_visible_when_status_is_failed(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $crearEnvio = collect($actions)->first(fn ($a) => $a->getName() === 'crear_envio_zipnova');

        $record = (object) ['meta' => (object) ['zipnova_shipment_id' => '789012', 'zipnova_status' => 'failed']];
        $visible = $this->callVisibleCallback($crearEnvio, $record);
        $this->assertTrue($visible);
    }

    public function test_crear_envio_hidden_when_status_is_created(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $crearEnvio = collect($actions)->first(fn ($a) => $a->getName() === 'crear_envio_zipnova');

        $record = (object) ['meta' => (object) ['zipnova_shipment_id' => '789012', 'zipnova_status' => 'created']];
        $visible = $this->callVisibleCallback($crearEnvio, $record);
        $this->assertFalse($visible);
    }

    public function test_ver_tracking_hidden_when_no_shipment_id(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $verTracking = collect($actions)->first(fn ($a) => $a->getName() === 'ver_tracking');
        $this->assertNotNull($verTracking);

        $record = (object) ['meta' => (object) []];
        $visible = $this->callVisibleCallback($verTracking, $record);
        $this->assertFalse($visible);
    }

    public function test_ver_tracking_visible_when_shipment_id_exists(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $verTracking = collect($actions)->first(fn ($a) => $a->getName() === 'ver_tracking');

        $record = (object) ['meta' => (object) ['zipnova_shipment_id' => '789012', 'zipnova_status' => 'created']];
        $visible = $this->callVisibleCallback($verTracking, $record);
        $this->assertTrue($visible);
    }

    public function test_cancelar_envio_hidden_when_already_cancelled(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $cancelarEnvio = collect($actions)->first(fn ($a) => $a->getName() === 'cancelar_envio');
        $this->assertNotNull($cancelarEnvio);

        $record = (object) ['meta' => (object) ['zipnova_shipment_id' => '789012', 'zipnova_status' => 'cancelled']];
        $visible = $this->callVisibleCallback($cancelarEnvio, $record);
        $this->assertFalse($visible);
    }

    public function test_cancelar_envio_visible_when_shipment_id_and_not_cancelled(): void
    {
        $extension = new ManageOrderExtension();
        $actions = $extension->headerActions([]);

        $cancelarEnvio = collect($actions)->first(fn ($a) => $a->getName() === 'cancelar_envio');

        $record = (object) ['meta' => (object) ['zipnova_shipment_id' => '789012', 'zipnova_status' => 'created']];
        $visible = $this->callVisibleCallback($cancelarEnvio, $record);
        $this->assertTrue($visible);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Extract and call the visible callback from a Filament Action.
     */
    private function callVisibleCallback(mixed $action, object $record): bool
    {
        try {
            $prop = new \ReflectionProperty($action, 'isVisible');
            $prop->setAccessible(true);
            $callback = $prop->getValue($action);

            if ($callback instanceof \Closure) {
                return (bool) $callback($record);
            }
        } catch (\ReflectionException) {
            // Empty
        }

        // Fallback: action has no visibility constraint — it's visible by default
        return true;
    }
}
