<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Por padrão cria uma loja nova. Nos testes, passe o tenant_id quando
            // o cliente precisar ser da loja de um usuário.
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'phone' => '11'.fake()->numerify('9########'),
            'email' => fake()->boolean(60) ? fake()->unique()->safeEmail() : null,
            'notes' => null,
        ];
    }
}