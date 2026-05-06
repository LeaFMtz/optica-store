<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionFieldResource\Pages;

use App\Filament\Resources\PrescriptionFieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrescriptionField extends CreateRecord
{
    protected static string $resource = PrescriptionFieldResource::class;
}
