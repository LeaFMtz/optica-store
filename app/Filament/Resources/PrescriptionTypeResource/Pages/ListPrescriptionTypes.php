<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionTypeResource\Pages;

use App\Filament\Resources\PrescriptionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrescriptionTypes extends ListRecords
{
    protected static string $resource = PrescriptionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
