<?php

declare(strict_types=1);

namespace App\Filament\Support\FieldTypes;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use Lunar\Admin\Support\FieldTypes\File;
use Lunar\Models\Attribute;

class ImageFileField extends File
{
    public static function getFilamentComponent(Attribute $attribute): Component
    {
        /** @var FileUpload $component */
        $component = parent::getFilamentComponent($attribute);

        return $component->image()->imagePreviewHeight('200');
    }
}
