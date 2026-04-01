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
                    'firstname' => 'Juan',
                    'lastname' => 'Pérez',
                    'email' => 'juan.perez@example.com',
                ],
                [
                    'firstname' => 'María',
                    'lastname' => 'González',
                    'email' => 'maria.gonzalez@example.com',
                ],
                [
                    'firstname' => 'Carlos',
                    'lastname' => 'Rodríguez',
                    'email' => 'carlos.rodriguez@example.com',
                ],
                [
                    'firstname' => 'Ana',
                    'lastname' => 'López',
                    'email' => 'ana.lopez@example.com',
                ],
                [
                    'firstname' => 'Pedro',
                    'lastname' => 'Fernández',
                    'email' => 'pedro.fernandez@example.com',
                ],
            ];

            foreach ($customerData as $data) {
                $customer = Customer::create([
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname'],
                    'email' => $data['email'],
                ]);

                $user = User::factory()->create([
                    'name' => "{$data['firstname']} {$data['lastname']}",
                    'email' => $data['email'],
                ]);

                $customer->users()->attach($user->id);

                Address::create([
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname'],
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
