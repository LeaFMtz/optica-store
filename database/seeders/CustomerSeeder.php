<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Address;
use Lunar\Models\Customer;

class CustomerSeeder extends AbstractSeeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $faker = Factory::create('es_AR');

            $customerData = [
                [
                    'first_name' => 'Juan',
                    'last_name' => 'Pérez',
                    'email' => 'juan.perez@example.com',
                ],
                [
                    'first_name' => 'María',
                    'last_name' => 'González',
                    'email' => 'maria.gonzalez@example.com',
                ],
                [
                    'first_name' => 'Carlos',
                    'last_name' => 'Rodríguez',
                    'email' => 'carlos.rodriguez@example.com',
                ],
                [
                    'first_name' => 'Ana',
                    'last_name' => 'López',
                    'email' => 'ana.lopez@example.com',
                ],
                [
                    'first_name' => 'Pedro',
                    'last_name' => 'Fernández',
                    'email' => 'pedro.fernandez@example.com',
                ],
            ];

            foreach ($customerData as $data) {
                $customer = Customer::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                ]);

                $user = User::factory()->create([
                    'name' => "{$data['first_name']} {$data['last_name']}",
                    'email' => $data['email'],
                ]);

                $customer->users()->attach($user->id);

                Address::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'company' => null,
                    'addressable_type' => (new Customer)->getMorphClass(),
                    'addressable_id' => $customer->id,
                    'address_line_one' => $faker->streetAddress(),
                    'address_line_two' => null,
                    'address_line_three' => null,
                    'city' => $faker->city(),
                    'state' => $faker->state(),
                    'postcode' => $faker->postcode(),
                    'country_id' => $argentinaId = 235,
                    'shipping_default' => true,
                    'billing_default' => true,
                ]);
            }
        });
    }
}
