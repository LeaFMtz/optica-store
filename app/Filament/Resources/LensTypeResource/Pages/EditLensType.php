<?php

declare(strict_types=1);

namespace App\Filament\Resources\LensTypeResource\Pages;

use App\Filament\Resources\LensTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditLensType extends EditRecord
{
    protected static string $resource = LensTypeResource::class;
}
