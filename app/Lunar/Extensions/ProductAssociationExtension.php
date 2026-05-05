<?php

declare(strict_types=1);

namespace App\Lunar\Extensions;

use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Schema;
use Lunar\Admin\Support\Extending\RelationPageExtension;

class ProductAssociationExtension extends RelationPageExtension
{
    public function extendForm(Schema $schema): Schema
    {
        return $schema->components([
            ...$schema->getComponents(),
            CheckboxList::make('selected_variants')
                ->label('Variantes')
                ->options(fn ($record) => $this->getVariantOptions($record))
                ->columns(3),
        ]);
    }

    protected function getVariantOptions($record): array
    {
        if (!$record || !$record->target) {
            return [];
        }

        return $record->target->variants()
            ->get()
            ->mapWithKeys(fn ($variant) => [
                $variant->id => $variant->sku . ' - ' . $variant->translateAttribute('name'),
            ])
            ->toArray();
    }
}