<?php

declare(strict_types=1);

namespace App\Models;

use Lunar\Models\Product as LunarProduct;

class CustomProduct extends LunarProduct
{
    protected $table = 'products';
}
