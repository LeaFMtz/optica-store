<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Banner extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'url',
        'position',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('mobile_image')->singleFile();
    }

    /**
     * Available positions for banners.
     */
    public static function positions(): array
    {
        return [
            'home_hero' => 'Home - Hero Principal',
            'home_middle' => 'Home - Banner Intermedio',
            'home_bottom' => 'Home - Banner Inferior',
            'home_newsletter' => 'Home - Banner Newsletter',
            'footer' => 'Footer',
        ];
    }
}
