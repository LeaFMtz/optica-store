<?php

declare(strict_types=1);

namespace Database\Seeders;

use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;

class ProductOptionSeeder extends AbstractSeeder
{
    public function run(): void
    {
        $options = [
            [
                'name' => 'Ojo',
                'handle' => 'ojo',
                'values' => ['Ojo Derecho', 'Ojo Izquierdo'],
            ],
            [
                'name' => 'Graduación (SPH)',
                'handle' => 'graduacion-sph',
                'values' => [
                    '-12.00', '-11.50', '-11.00', '-10.50', '-10.00', '-9.50', '-9.00', '-8.50', '-8.00', '-7.50',
                    '-7.00', '-6.50', '-6.00', '-5.50', '-5.00', '-4.75', '-4.50', '-4.25', '-4.00', '-3.75',
                    '-3.50', '-3.25', '-3.00', '-2.75', '-2.50', '-2.25', '-2.00', '-1.75', '-1.50', '-1.25',
                    '-1.00', '-0.75', '-0.50',
                    '+0.50', '+0.75', '+1.00', '+1.25', '+1.50', '+1.75', '+2.00', '+2.25', '+2.50', '+2.75',
                    '+3.00', '+3.25', '+3.50', '+3.75', '+4.00', '+4.50', '+5.00', '+5.50', '+6.00',
                ],
            ],
            [
                'name' => 'Curva Base (BC)',
                'handle' => 'curva-base',
                'values' => ['8.3', '8.4', '8.5', '8.6', '8.7', '8.8', '8.9', '9.0'],
            ],
            [
                'name' => 'Diámetro (DIA)',
                'handle' => 'diametro',
                'values' => ['13.5', '14.0', '14.2', '14.5'],
            ],
            [
                'name' => 'Cilindro (CYL)',
                'handle' => 'cilindro',
                'values' => ['-0.75', '-1.00', '-1.25', '-1.50', '-1.75', '-2.00'],
            ],
            [
                'name' => 'Eje (AXIS)',
                'handle' => 'eje',
                'values' => ['10', '20', '30', '40', '50', '60', '70', '80', '90', '100', '110', '120', '130', '140', '150', '160', '170', '180'],
            ],
            [
                'name' => 'Color',
                'handle' => 'color',
                'values' => ['Transparente', 'Azul', 'Verde', 'Marrón', 'Gris', 'Violeta'],
            ],
            [
                'name' => 'Talla',
                'handle' => 'talla',
                'values' => ['XS', 'S', 'M', 'L', 'XL'],
            ],
        ];

        foreach ($options as $optionData) {
            $option = ProductOption::updateOrCreate(
                ['handle' => $optionData['handle']],
                [
                    'name' => ['es' => $optionData['name']],
                    'label' => ['es' => $optionData['name']],
                    'shared' => true,
                ],
            );

            foreach ($optionData['values'] as $value) {
                ProductOptionValue::updateOrCreate(
                    [
                        'product_option_id' => $option->id,
                        'name' => ['es' => $value],
                    ],
                    [
                        'position' => array_search($value, $optionData['values']),
                    ],
                );
            }
        }
    }
}
