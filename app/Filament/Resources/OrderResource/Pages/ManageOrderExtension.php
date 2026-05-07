<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\FontWeight;
use Lunar\Admin\Support\Extending\ViewPageExtension;

class ManageOrderExtension extends ViewPageExtension
{
    public function extendInfolistSchema(array $schema): array
    {
        $section = Section::make('receta_optica')
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

        // Insert after order lines table (index 1) — before totals/notas (index 2)
        array_splice($schema, 2, 0, [$section]);

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, string>
     */
    private function buildPrescriptionRows(array $meta): array
    {
        $data = $meta['prescription_data'] ?? null;

        if (empty($data)) {
            return [];
        }

        $rows = [];

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
            $rows['DP'] = ($data['pd_od'] ?? '—') . ' / ' . ($data['pd_oi'] ?? '—');
        }

        return $rows;
    }
}
