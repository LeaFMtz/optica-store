<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionTypeResource\Pages;

use App\Filament\Resources\PrescriptionTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePrescriptionType extends CreateRecord
{
    protected static string $resource = PrescriptionTypeResource::class;
}
