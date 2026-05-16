<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages;

use App\Services\ZipnovaService;
use Filament\Actions\Action;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;
use Lunar\Admin\Support\Extending\ViewPageExtension;

class ManageOrderExtension extends ViewPageExtension
{
    public function extendInfolistSchema(array $schema): array
    {
        $prescriptionSection = Section::make('receta_optica')
            ->heading('Receta Óptica')
            ->compact()
            ->schema(function ($record): array {
                $lines = collect($record->lines ?? [])
                    ->filter(fn ($line) => !empty(((array) ($line->meta ?? []))['prescription_data'] ?? null));

                if ($lines->isEmpty()) {
                    return [];
                }

                return $lines->flatMap(function ($line): array {
                    $rows = $this->buildPrescriptionRows((array) ($line->meta ?? []));

                    return [
                        TextEntry::make("product_{$line->id}")
                            ->label('Producto')
                            ->getStateUsing(fn () => $line->description)
                            ->weight(FontWeight::Bold),
                        KeyValueEntry::make("rx_{$line->id}")
                            ->label('Datos de graduación')
                            ->getStateUsing(fn () => $rows),
                    ];
                })->all();
            })
            ->hidden(function ($record): bool {
                return collect($record->lines ?? [])
                    ->filter(fn ($line) => !empty(((array) ($line->meta ?? []))['prescription_data'] ?? null))
                    ->isEmpty();
            });

        $zipnovaSection = Section::make('zipnova_envio')
            ->heading('Envío Zipnova')
            ->compact()
            ->hidden(fn ($record): bool => empty(((array) ($record->meta ?? []))['zipnova_shipment_id']))
            ->schema([
                TextEntry::make('zipnova_shipment_id')
                    ->label('ID Shipment')
                    ->getStateUsing(fn ($record) => ((array) ($record->meta ?? []))['zipnova_shipment_id'] ?? '—'),
                TextEntry::make('zipnova_status')
                    ->label('Estado')
                    ->getStateUsing(fn ($record) => ((array) ($record->meta ?? []))['zipnova_status'] ?? '—'),
                TextEntry::make('zipnova_label_code')
                    ->label('Código de etiqueta')
                    ->getStateUsing(fn ($record) => ((array) ($record->meta ?? []))['zipnova_label_code'] ?? '—'),
            ]);

        // Insert prescription section at index 2 (after order lines table at index 1)
        array_splice($schema, 2, 0, [$prescriptionSection]);

        // Insert Zipnova section at index 3 (after prescription section)
        array_splice($schema, 3, 0, [$zipnovaSection]);

        return $schema;
    }

    /**
     * @param  Action[]  $actions
     * @return Action[]
     */
    public function headerActions(array $actions): array
    {
        $crearEnvio = Action::make('crear_envio_zipnova')
            ->label('Crear envío')
            ->icon('heroicon-o-truck')
            ->color('success')
            ->visible(function ($record): bool {
                $meta = (array) ($record->meta ?? []);
                $status = $meta['zipnova_status'] ?? null;

                return $status === null || $status === 'failed';
            })
            ->action(function ($record): void {
                $meta = (array) ($record->meta ?? []);
                $identifier = (string) ($record->shippingAddress?->shipping_option ?? '');

                if (!str_starts_with($identifier, 'ZN_')) {
                    Notification::make()
                        ->title('Este pedido no tiene envío Zipnova asignado.')
                        ->warning()
                        ->send();

                    return;
                }

                $parts = explode('_', $identifier);
                $serviceType = count($parts) >= 3 ? implode('_', array_slice($parts, 2)) : '';

                try {
                    /** @var ZipnovaService $zipnova */
                    $zipnova = app(ZipnovaService::class);
                    $result = $zipnova->createShipment($record, $serviceType);

                    $meta['zipnova_shipment_id'] = $result['id'];
                    $meta['zipnova_label_code'] = $result['label_code'];
                    $meta['zipnova_status'] = 'created';
                    $record->meta = $meta;
                    $record->save();

                    Notification::make()
                        ->title('Envío creado correctamente.')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Error al crear el envío: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });

        $verTracking = Action::make('ver_tracking')
            ->label('Ver tracking')
            ->icon('heroicon-o-map-pin')
            ->color('info')
            ->visible(function ($record): bool {
                $meta = (array) ($record->meta ?? []);

                return !empty($meta['zipnova_shipment_id']);
            })
            ->modalHeading('Tracking del envío')
            ->modalContent(function ($record) {
                $meta = (array) ($record->meta ?? []);
                $shipmentId = (string) ($meta['zipnova_shipment_id'] ?? '');

                try {
                    /** @var ZipnovaService $zipnova */
                    $zipnova = app(ZipnovaService::class);
                    $tracking = $zipnova->getTracking($shipmentId);
                    $events = $tracking['events'] ?? [];

                    return view('filament.zipnova-tracking', compact('tracking', 'events'));
                } catch (\Throwable $e) {
                    return view('filament.zipnova-tracking', [
                        'tracking' => ['id' => $shipmentId, 'status' => 'error'],
                        'events' => [],
                        'error' => $e->getMessage(),
                    ]);
                }
            })
            ->modalSubmitAction(false);

        $cancelarEnvio = Action::make('cancelar_envio')
            ->label('Cancelar envío')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('¿Cancelar el envío?')
            ->modalDescription('Esta acción no se puede deshacer.')
            ->visible(function ($record): bool {
                $meta = (array) ($record->meta ?? []);

                return !empty($meta['zipnova_shipment_id'])
                    && ($meta['zipnova_status'] ?? '') !== 'cancelled';
            })
            ->action(function ($record): void {
                $meta = (array) ($record->meta ?? []);
                $shipmentId = (string) ($meta['zipnova_shipment_id'] ?? '');

                try {
                    /** @var ZipnovaService $zipnova */
                    $zipnova = app(ZipnovaService::class);
                    $zipnova->cancelShipment($shipmentId);

                    $meta['zipnova_status'] = 'cancelled';
                    $record->meta = $meta;
                    $record->save();

                    Notification::make()
                        ->title('Envío cancelado correctamente.')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Error al cancelar el envío: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            });

        return array_merge([$crearEnvio, $verTracking, $cancelarEnvio], $actions);
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, string>
     */
    private function buildPrescriptionRows(array $meta): array
    {
        $rows = [];

        if (!empty($meta['lens_use_name'])) {
            $rows['Uso'] = $meta['lens_use_name'];
        }

        if (!empty($meta['lens_type_name'])) {
            $rows['Tipo'] = $meta['lens_type_name'];
        }

        $data = $meta['prescription_data'] ?? null;

        if (empty($data)) {
            return $rows;
        }

        foreach (['od' => 'OD', 'oi' => 'OI'] as $prefix => $label) {
            $eyeParts = [];
            foreach ($data as $key => $value) {
                if (!str_starts_with($key, "{$prefix}_")) {
                    continue;
                }
                if ($value === null || $value === '') {
                    continue;
                }
                $fieldKey = substr($key, strlen("{$prefix}_"));
                $fieldLabel = ucfirst(substr($fieldKey, 0, 3));
                $formatted = is_numeric($value)
                    ? ((float) $value >= 0 ? "+{$value}" : (string) $value)
                    : (string) $value;
                $eyeParts[] = "{$fieldLabel} {$formatted}";
            }
            if (!empty($eyeParts)) {
                $rows[$label] = implode('  ·  ', $eyeParts);
            }
        }

        if (isset($data['pd']) && $data['pd'] !== null && $data['pd'] !== '') {
            $rows['DP'] = (string) $data['pd'];
        } elseif (isset($data['pd_od']) || isset($data['pd_oi'])) {
            $rows['DP'] = ($data['pd_od'] ?? '—').' / '.($data['pd_oi'] ?? '—');
        }

        return $rows;
    }
}
