<?php

declare(strict_types=1);

namespace App\Filament\Resources\LensTypeResource\Pages;

use App\Filament\Resources\LensTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLensTypes extends ListRecords
{
    protected static string $resource = LensTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
