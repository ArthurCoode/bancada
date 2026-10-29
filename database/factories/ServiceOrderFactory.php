<?php

namespace Database\Factories;

use App\Enums\ServiceOrderStatus;
use App\Models\Device;
use App\Models\ServiceOrder;
use App\Services\ServiceOrderService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOrder>
 */
class ServiceOrderFactory extends Factory
{
    /**
     * Use sempre ->forDevice($aparelho): ele define loja, cliente e aparelho
     * de forma consistente e já reserva o próximo número da loja.
     */
    public function definition(): array
    {
        return [
            'status' => ServiceOrderStatus::Received,
            'reported_issue' => fake()->sentence(),
            'entry_checklist' => null,
        ];
    }

    public function forDevice(Device $device): static
    {
        return $this->state(fn () => [
            'tenant_id' => $device->tenant_id,
            'customer_id' => $device->customer_id,
            'device_id' => $device->id,
            'number' => app(ServiceOrderService::class)->nextNumber($device->tenant_id),
        ]);
    }
}