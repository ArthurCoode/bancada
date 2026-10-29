<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\ServiceOrderStatus;
use App\Models\Customer;
use App\Models\Device;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create();
    }

    private function member(User $owner, Role $role): User
    {
        return User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => $role]);
    }

    private function customerOf(User $user, array $attributes = []): Customer
    {
        return Customer::factory()->create(array_merge(['tenant_id' => $user->tenant_id], $attributes));
    }

    private function deviceOf(Customer $customer, array $attributes = []): Device
    {
        return Device::factory()->forCustomer($customer)->create($attributes);
    }

    private function orderOf(Device $device, array $attributes = []): ServiceOrder
    {
        return ServiceOrder::factory()->forDevice($device)->create($attributes);
    }

    private function validData(Customer $customer, Device $device, array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $customer->id,
            'device_id' => $device->id,
            'technician_id' => '',
            'reported_issue' => 'Não liga após queda.',
            'accessories' => 'Capinha e carregador',
            'device_password' => '1234',
            'due_date' => now()->addDays(3)->toDateString(),
            'internal_notes' => '',
            'checklist' => ['screen' => 'defective', 'touch' => 'ok', 'hacker' => 'ok'],
        ], $overrides);
    }

    /** @return array{0: User, 1: Customer, 2: Device} */
    private function scenario(): array
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);
        $device = $this->deviceOf($customer);

        return [$owner, $customer, $device];
    }

    // ---------- acesso, listagem e busca ----------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/ordens')->assertRedirect(route('login'));
    }

    public function test_list_shows_only_orders_from_own_tenant(): void
    {
        [$owner, $customer, $device] = $this->scenario();
        $this->orderOf($device, ['reported_issue' => 'Defeito da minha loja']);

        $strangerDevice = $this->deviceOf(Customer::factory()->create(['name' => 'Cliente Estranho']));
        $this->orderOf($strangerDevice);

        $this->actingAs($owner)->get('/ordens')
            ->assertOk()
            ->assertSee('0001')
            ->assertSee($customer->name)
            ->assertDontSee('Cliente Estranho');
    }

    public function test_orders_can_be_filtered_by_status(): void
    {
        [$owner, $customer, $device] = $this->scenario();
        $other = $this->deviceOf($this->customerOf($owner, ['name' => 'Cliente Pronto']));

        $this->orderOf($device);
        $this->orderOf($other, ['status' => ServiceOrderStatus::Ready]);

        $this->actingAs($owner)->get('/ordens?status=ready')
            ->assertSee('Cliente Pronto')
            ->assertDontSee($customer->name);
    }

    public function test_orders_can_be_searched_by_number_and_customer_name(): void
    {
        $owner = $this->owner();
        $ana = $this->customerOf($owner, ['name' => 'Ana Paula']);
        $bruno = $this->customerOf($owner, ['name' => 'Bruno Costa']);

        $this->orderOf($this->deviceOf($ana));    // OS 1
        $this->orderOf($this->deviceOf($bruno));  // OS 2

        $this->actingAs($owner);

        $this->get('/ordens?q=paula')->assertSee('Ana Paula')->assertDontSee('Bruno Costa');
        $this->get('/ordens?q=2')->assertSee('Bruno Costa')->assertDontSee('Ana Paula');
    }

    public function test_long_numeric_search_matches_the_device_serial(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner, ['name' => 'Ana Paula']);
        $this->orderOf($this->deviceOf($customer, ['serial_number' => '356938035643809']));

        $this->actingAs($owner)->get('/ordens?q=356938035643809')
            ->assertOk()
            ->assertSee('Ana Paula');
    }
    
    // ---------- formulário ----------

    public function test_create_form_requires_a_customer_of_own_tenant(): void
    {
        [$owner, $customer, $device] = $this->scenario();
        $stranger = Customer::factory()->create();

        $this->actingAs($owner);

        $this->get('/ordens/nova')->assertRedirect(route('customers.index'));
        $this->get("/ordens/nova?customer={$stranger->id}")->assertNotFound();
        $this->get("/ordens/nova?customer={$customer->id}&device={$device->id}")
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee('Checklist de entrada');
    }

    public function test_create_form_warns_when_customer_has_no_devices(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);

        $this->actingAs($owner)->get("/ordens/nova?customer={$customer->id}")
            ->assertOk()
            ->assertSee('ainda não tem aparelhos');
    }

    // ---------- abertura ----------

    public function test_user_can_open_an_order(): void
    {
        [$owner, $customer, $device] = $this->scenario();

        $response = $this->actingAs($owner)->post('/ordens', $this->validData($customer, $device));

        $order = ServiceOrder::withoutGlobalScopes()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame(1, $order->number);
        $this->assertSame('0001', $order->displayNumber());
        $this->assertSame(ServiceOrderStatus::Received, $order->status);
        $this->assertSame($owner->tenant_id, $order->tenant_id);
        $this->assertSame($owner->id, $order->created_by);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame($device->id, $order->device_id);
    }

    public function test_opening_an_order_registers_the_first_history_entry(): void
    {
        [$owner, $customer, $device] = $this->scenario();

        $this->actingAs($owner)->post('/ordens', $this->validData($customer, $device));

        $order = ServiceOrder::withoutGlobalScopes()->firstOrFail();
        $history = ServiceOrderStatusHistory::withoutGlobalScopes()->where('service_order_id', $order->id)->get();

        $this->assertCount(1, $history);
        $this->assertNull($history[0]->from_status);
        $this->assertSame(ServiceOrderStatus::Received, $history[0]->to_status);
        $this->assertSame($owner->id, $history[0]->user_id);
    }

    public function test_checklist_keeps_only_official_items_and_defaults_to_untested(): void
    {
        [$owner, $customer, $device] = $this->scenario();

        $this->actingAs($owner)->post('/ordens', $this->validData($customer, $device));

        $checklist = ServiceOrder::withoutGlobalScopes()->firstOrFail()->entry_checklist;

        $this->assertSame(array_keys(ServiceOrder::CHECKLIST_ITEMS), array_keys($checklist));
        $this->assertSame('defective', $checklist['screen']);
        $this->assertSame('ok', $checklist['touch']);
        $this->assertSame('untested', $checklist['camera']);
        $this->assertArrayNotHasKey('hacker', $checklist);
    }

    public function test_device_password_is_encrypted_at_rest(): void
    {
        [$owner, $customer, $device] = $this->scenario();

        $this->actingAs($owner)->post('/ordens', $this->validData($customer, $device, ['device_password' => 'segredo9876']));

        $order = ServiceOrder::withoutGlobalScopes()->firstOrFail();
        $raw = DB::table('service_orders')->where('id', $order->id)->value('device_password');

        $this->assertNotSame('segredo9876', $raw);
        $this->assertStringNotContainsString('segredo9876', $raw);
        $this->assertSame('segredo9876', $order->device_password);
        $this->assertArrayNotHasKey('device_password', $order->toArray());
    }

    public function test_numbers_are_sequential_per_tenant(): void
    {
        [$ownerA, $customerA, $deviceA] = $this->scenario();
        [$ownerB, $customerB, $deviceB] = $this->scenario();

        $this->actingAs($ownerA)->post('/ordens', $this->validData($customerA, $deviceA));
        $this->actingAs($ownerA)->post('/ordens', $this->validData($customerA, $deviceA));
        $this->actingAs($ownerB)->post('/ordens', $this->validData($customerB, $deviceB));

        $numbersA = ServiceOrder::withoutGlobalScopes()->where('tenant_id', $ownerA->tenant_id)->orderBy('number')->pluck('number')->all();
        $numbersB = ServiceOrder::withoutGlobalScopes()->where('tenant_id', $ownerB->tenant_id)->pluck('number')->all();

        $this->assertSame([1, 2], $numbersA);
        $this->assertSame([1], $numbersB);
    }

    public function test_any_role_can_open_an_order(): void
    {
        [$owner, $customer, $device] = $this->scenario();

        $this->actingAs($this->member($owner, Role::Attendant))
            ->post('/ordens', $this->validData($customer, $device))
            ->assertRedirect();

        $this->assertDatabaseCount('service_orders', 1);
    }

    public function test_status_number_tenant_and_author_sent_in_request_are_ignored(): void
    {
        [$owner, $customer, $device] = $this->scenario();
        $other = Tenant::factory()->create();

        $this->actingAs($owner)->post('/ordens', $this->validData($customer, $device, [
            'status' => 'delivered',
            'number' => 99,
            'tenant_id' => $other->id,
            'created_by' => 999,
        ]));

        $order = ServiceOrder::withoutGlobalScopes()->firstOrFail();

        $this->assertSame(ServiceOrderStatus::Received, $order->status);
        $this->assertSame(1, $order->number);
        $this->assertSame($owner->tenant_id, $order->tenant_id);
        $this->assertSame($owner->id, $order->created_by);
    }

    // ---------- validação cruzada (ids dentro da loja) ----------

    public function test_cannot_use_a_customer_from_another_tenant(): void
    {
        [$owner, , $device] = $this->scenario();
        $strangerCustomer = Customer::factory()->create();

        $this->actingAs($owner)
            ->post('/ordens', $this->validData($strangerCustomer, $device))
            ->assertSessionHasErrors('customer_id');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_cannot_use_a_device_from_another_tenant(): void
    {
        [$owner, $customer] = $this->scenario();
        $strangerCustomer = Customer::factory()->create();
        $strangerDevice = $this->deviceOf($strangerCustomer);

        $this->actingAs($owner)
            ->post('/ordens', $this->validData($customer, $strangerDevice))
            ->assertSessionHasErrors('device_id');

        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_device_must_belong_to_the_informed_customer(): void
    {
        [$owner, $customer] = $this->scenario();
        $otherCustomer = $this->customerOf($owner);
        $otherDevice = $this->deviceOf($otherCustomer); // mesma loja, outro cliente

        $this->actingAs($owner)
            ->post('/ordens', $this->validData($customer, $otherDevice))
            ->assertSessionHasErrors('device_id');
    }

    public function test_technician_must_be_from_own_tenant_and_have_the_right_role(): void
    {
        [$owner, $customer, $device] = $this->scenario();
        $attendant = $this->member($owner, Role::Attendant);
        $strangerTechnician = User::factory()->create(['role' => Role::Technician]);
        $technician = $this->member($owner, Role::Technician);

        $this->actingAs($owner);

        $this->post('/ordens', $this->validData($customer, $device, ['technician_id' => $attendant->id]))
            ->assertSessionHasErrors('technician_id');

        $this->post('/ordens', $this->validData($customer, $device, ['technician_id' => $strangerTechnician->id]))
            ->assertSessionHasErrors('technician_id');

        $this->post('/ordens', $this->validData($customer, $device, ['technician_id' => $technician->id]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame($technician->id, ServiceOrder::withoutGlobalScopes()->firstOrFail()->technician_id);
    }

    public function test_issue_is_required_and_due_date_cannot_be_in_the_past(): void
    {
        [$owner, $customer, $device] = $this->scenario();

        $this->actingAs($owner)->post('/ordens', $this->validData($customer, $device, [
            'reported_issue' => '',
            'due_date' => now()->subDay()->toDateString(),
        ]))->assertSessionHasErrors(['reported_issue', 'due_date']);
    }

    // ---------- detalhes ----------

    public function test_order_from_another_tenant_is_not_found(): void
    {
        $owner = $this->owner();
        $strangerOrder = $this->orderOf($this->deviceOf(Customer::factory()->create()));

        $this->actingAs($owner)->get("/ordens/{$strangerOrder->id}")->assertNotFound();
    }

    public function test_show_page_displays_the_order(): void
    {
        [$owner, $customer, $device] = $this->scenario();
        $order = $this->orderOf($device, ['reported_issue' => 'Tela quebrada']);

        $this->actingAs($owner)->get("/ordens/{$order->id}")
            ->assertOk()
            ->assertSee('OS 0001')
            ->assertSee('Tela quebrada')
            ->assertSee($customer->name)
            ->assertSee('Recebido');
    }

    public function test_device_password_is_shown_only_to_owner_and_technician(): void
    {
        [$owner, , $device] = $this->scenario();
        $order = $this->orderOf($device, ['device_password' => 'segredo9876']);

        $this->actingAs($owner)->get("/ordens/{$order->id}")->assertSee('segredo9876');
        $this->actingAs($this->member($owner, Role::Technician))->get("/ordens/{$order->id}")->assertSee('segredo9876');

        $this->actingAs($this->member($owner, Role::Attendant))->get("/ordens/{$order->id}")
            ->assertOk()
            ->assertDontSee('segredo9876');
    }

    // ---------- integridade ----------

    public function test_device_with_orders_cannot_be_deleted(): void
    {
        [$owner, , $device] = $this->scenario();
        $this->orderOf($device);

        $this->actingAs($owner)->delete("/aparelhos/{$device->id}")
            ->assertRedirect(route('devices.edit', $device))
            ->assertSessionHas('error');

        $this->assertModelExists($device);
    }

    public function test_removing_the_technician_keeps_the_order(): void
    {
        [$owner, , $device] = $this->scenario();
        $technician = $this->member($owner, Role::Technician);
        $order = $this->orderOf($device, ['technician_id' => $technician->id]);

        $this->actingAs($owner)->delete("/equipe/{$technician->id}")->assertRedirect();

        $this->assertModelExists($order);
        $this->assertNull($order->fresh()->technician_id);
    }

    public function test_display_number_is_zero_padded(): void
    {
        $order = new ServiceOrder();
        $order->number = 7;

        $this->assertSame('0007', $order->displayNumber());
    }
}