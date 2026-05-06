<?php

declare(strict_types=1);

namespace App\Filament\Resources\LensQualityResource\Pages;

use App\Filament\Resources\LensQualityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLensQualities extends ListRecords
{
    protected static string $resource = LensQualityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
