<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create();
    }

    private function technician(User $owner): User
    {
        return User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => Role::Technician]);
    }

    private function customerOf(User $user, array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge(['tenant_id' => $user->tenant_id], $attributes));
    }

    private function deviceOf(Customer $customer, array $attributes = []): Device
    {
        return Device::factory()->forCustomer($customer)->create($attributes);
    }

    private function validData(int $customerId, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customerId,
            'brand' => 'Samsung',
            'model' => 'Galaxy A54',
            'serial_number' => '356938035643809',
            'color' => 'Preto',
            'notes' => 'Tela trincada.',
        ], $overrides);
    }

    // ---------- acesso, listagem e busca ----------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/aparelhos')->assertRedirect(route('login'));
    }

    public function test_list_shows_only_devices_from_own_tenant(): void
    {
        $owner = $this->owner();
        $this->deviceOf($this->customerOf($owner), ['brand' => 'MarcaDaMinhaLoja']);

        $stranger = Customer::factory()->create();
        $this->deviceOf($stranger, ['brand' => 'MarcaDeOutraLoja']);

        $this->actingAs($owner)->get('/aparelhos')
            ->assertOk()
            ->assertSee('MarcaDaMinhaLoja')
            ->assertDontSee('MarcaDeOutraLoja');
    }

    public function test_search_by_serial_model_and_customer_name(): void
    {
        $owner = $this->owner();
        $ana = $this->customerOf($owner, ['name' => 'Ana Paula']);
        $bruno = $this->customerOf($owner, ['name' => 'Bruno Costa']);

        $this->deviceOf($ana, ['brand' => 'Apple', 'model' => 'iPhone 13', 'serial_number' => 'AAA111']);
        $this->deviceOf($bruno, ['brand' => 'Motorola', 'model' => 'Moto G', 'serial_number' => 'BBB222']);

        $this->actingAs($owner);

        $this->get('/aparelhos?q=AAA111')->assertSee('iPhone 13')->assertDontSee('Moto G');
        $this->get('/aparelhos?q=moto')->assertSee('Moto G')->assertDontSee('iPhone 13');
        $this->get('/aparelhos?q=ana')->assertSee('iPhone 13')->assertDontSee('Moto G');
    }

    // ---------- formulário de cadastro ----------

    public function test_create_form_requires_a_customer_of_own_tenant(): void
    {
        $owner = $this->owner();
        $mine = $this->customerOf($owner, ['name' => 'Cliente Da Loja']);
        $stranger = Customer::factory()->create();

        $this->actingAs($owner);

        $this->get('/aparelhos/novo')->assertRedirect(route('customers.index'));
        $this->get("/aparelhos/novo?customer={$stranger->id}")->assertNotFound();
        $this->get("/aparelhos/novo?customer={$mine->id}")->assertOk()->assertSee('Cliente Da Loja');
    }

    // ---------- cadastro ----------

    public function test_user_can_register_a_device_for_own_customer(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);

        $this->actingAs($owner)
            ->post('/aparelhos', $this->validData($customer->id, ['serial_number' => 'abc-123']))
            ->assertRedirect(route('customers.show', $customer));

        $device = Device::withoutGlobalScopes()->firstOrFail();

        $this->assertSame($owner->tenant_id, $device->tenant_id);
        $this->assertSame($customer->id, $device->customer_id);
        $this->assertSame('ABC-123', $device->serial_number);
    }

    public function test_technician_can_register_devices(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);

        $this->actingAs($this->technician($owner))
            ->post('/aparelhos', $this->validData($customer->id))
            ->assertRedirect();

        $this->assertDatabaseCount('devices', 1);
    }

    public function test_cannot_register_a_device_for_a_customer_of_another_tenant(): void
    {
        $owner = $this->owner();
        $stranger = Customer::factory()->create();

        $this->actingAs($owner)
            ->post('/aparelhos', $this->validData($stranger->id))
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseCount('devices', 0);
    }

    public function test_cannot_register_a_device_for_a_nonexistent_customer(): void
    {
        $this->actingAs($this->owner())
            ->post('/aparelhos', $this->validData(999999))
            ->assertSessionHasErrors('customer_id');
    }

    public function test_brand_and_model_are_required(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);

        $this->actingAs($owner)
            ->post('/aparelhos', $this->validData($customer->id, ['brand' => '', 'model' => '']))
            ->assertSessionHasErrors(['brand', 'model']);
    }

    public function test_serial_number_rejects_invalid_characters(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);

        $this->actingAs($owner)
            ->post('/aparelhos', $this->validData($customer->id, ['serial_number' => 'abc 123!']))
            ->assertSessionHasErrors('serial_number');
    }

    public function test_tenant_id_sent_in_request_is_ignored(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);
        $other = Tenant::factory()->create();

        $this->actingAs($owner)
            ->post('/aparelhos', $this->validData($customer->id, ['tenant_id' => $other->id]));

        $this->assertSame($owner->tenant_id, Device::withoutGlobalScopes()->firstOrFail()->tenant_id);
    }

    // ---------- edição ----------

    public function test_user_can_update_a_device_but_not_move_it_to_another_customer(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);
        $otherCustomer = $this->customerOf($owner);
        $device = $this->deviceOf($customer);

        $this->actingAs($owner)
            ->put("/aparelhos/{$device->id}", $this->validData($otherCustomer->id, ['brand' => 'Motorola']))
            ->assertRedirect(route('customers.show', $customer));

        $device->refresh();

        $this->assertSame('Motorola', $device->brand);
        $this->assertSame($customer->id, $device->customer_id);
    }

    // ---------- isolamento ----------

    public function test_device_from_another_tenant_is_not_found(): void
    {
        $owner = $this->owner();
        $strangerDevice = $this->deviceOf(Customer::factory()->create(), ['brand' => 'Original']);

        $this->actingAs($owner);

        $this->get("/aparelhos/{$strangerDevice->id}/editar")->assertNotFound();
        $this->put("/aparelhos/{$strangerDevice->id}", $this->validData($strangerDevice->customer_id, ['brand' => 'Invasor']))
            ->assertNotFound();
        $this->delete("/aparelhos/{$strangerDevice->id}")->assertNotFound();

        $this->assertSame('Original', $strangerDevice->fresh()->brand);
    }

    // ---------- remoção ----------

    public function test_only_owner_can_delete_devices(): void
    {
        $owner = $this->owner();
        $technician = $this->technician($owner);
        $customer = $this->customerOf($owner);
        $device = $this->deviceOf($customer);

        $this->actingAs($technician)->delete("/aparelhos/{$device->id}")->assertForbidden();
        $this->assertModelExists($device);

        $this->actingAs($owner)->delete("/aparelhos/{$device->id}")
            ->assertRedirect(route('customers.show', $customer));
        $this->assertModelMissing($device);
    }

    public function test_customer_with_devices_cannot_be_deleted(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);
        $device = $this->deviceOf($customer);

        $this->actingAs($owner)->delete("/clientes/{$customer->id}")
            ->assertRedirect(route('customers.show', $customer))
            ->assertSessionHas('error');

        $this->assertModelExists($customer);

        // Sem aparelhos, a remoção passa a funcionar.
        $device->delete();

        $this->actingAs($owner)->delete("/clientes/{$customer->id}")
            ->assertRedirect(route('customers.index'));
        $this->assertModelMissing($customer);
    }

    // ---------- tela do cliente e model ----------

    public function test_customer_page_lists_its_devices(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);
        $this->deviceOf($customer, ['brand' => 'Samsung', 'model' => 'Galaxy A54']);

        $this->actingAs($owner)->get("/clientes/{$customer->id}")
            ->assertOk()
            ->assertSee('Samsung Galaxy A54')
            ->assertSee('Adicionar aparelho');
    }

    public function test_device_label_combines_brand_and_model(): void
    {
        $device = new Device(['brand' => 'Samsung', 'model' => 'Galaxy A54']);

        $this->assertSame('Samsung Galaxy A54', $device->label());
    }
}