<?php

declare(strict_types=1);

namespace App\Filament\Resources\LensUseResource\Pages;

use App\Filament\Resources\LensUseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLensUses extends ListRecords
{
    protected static string $resource = LensUseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
