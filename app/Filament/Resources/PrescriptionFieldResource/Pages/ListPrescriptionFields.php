<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionFieldResource\Pages;

use App\Filament\Resources\PrescriptionFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrescriptionFields extends ListRecords
{
    protected static string $resource = PrescriptionFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
