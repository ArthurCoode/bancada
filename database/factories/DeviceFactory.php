<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    /**
     * Sem customer_id/tenant_id de propósito: use sempre ->forCustomer($cliente).
     * Assim é impossível criar, sem querer, um aparelho de uma loja ligado a um
     * cliente de outra (esquecer o estado gera erro de banco, não dado torto).
     */
    public function definition(): array
    {
        return [
            'brand' => fake()->randomElement(['Samsung', 'Apple', 'Motorola', 'Xiaomi', 'LG']),
            'model' => fake()->bothify('Modelo ??-###'),
            'serial_number' => fake()->numerify('###############'),
            'color' => fake()->safeColorName(),
            'notes' => null,
        ];
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn () => [
            'customer_id' => $customer->id,
            'tenant_id' => $customer->tenant_id,
        ]);
    }
}