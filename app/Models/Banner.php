<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'url',
        'position',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

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
