<?php

declare(strict_types=1);

namespace App\Filament\Resources\PrescriptionFieldResource\Pages;

use App\Filament\Resources\PrescriptionFieldResource;
use Filament\Resources\Pages\EditRecord;

class EditPrescriptionField extends EditRecord
{
    protected static string $resource = PrescriptionFieldResource::class;
}
