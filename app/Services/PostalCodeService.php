<?php

declare(strict_types=1);

namespace App\Services;

class PostalCodeService
{
    private static ?array $map = null;

    /**
     * Resolve city and province from a 4-digit Argentine postal code.
     *
     * @return array{city: string, state: string}|null
     */
    public function lookup(string $cp): ?array
    {
        if (self::$map === null) {
            $path = storage_path('app/ar_postal_codes.json');
            self::$map = json_decode((string) file_get_contents($path), true) ?? [];
        }

        return self::$map[$cp] ?? null;
    }
}
