<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
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

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Cliente',
            'phone' => '(11) 98765-4321',
            'email' => 'Maria@Example.com',
            'notes' => 'Prefere contato à tarde.',
        ], $overrides);
    }

    // ---------- acesso ----------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/clientes')->assertRedirect(route('login'));
    }

    // ---------- listagem e busca ----------

    public function test_list_shows_only_customers_from_own_tenant(): void
    {
        $owner = $this->owner();
        $this->customerOf($owner, ['name' => 'Cliente Da Minha Loja']);
        Customer::factory()->create(['name' => 'Cliente De Outra Loja']);

        $this->actingAs($owner)->get('/clientes')
            ->assertOk()
            ->assertSee('Cliente Da Minha Loja')
            ->assertDontSee('Cliente De Outra Loja');
    }

    public function test_search_by_name(): void
    {
        $owner = $this->owner();
        $this->customerOf($owner, ['name' => 'Ana Paula']);
        $this->customerOf($owner, ['name' => 'Bruno Costa']);

        $this->actingAs($owner)->get('/clientes?q=paula')
            ->assertSee('Ana Paula')
            ->assertDontSee('Bruno Costa');
    }

    public function test_search_by_phone_ignores_formatting(): void
    {
        $owner = $this->owner();
        $this->customerOf($owner, ['name' => 'Ana Paula', 'phone' => '11987654321']);
        $this->customerOf($owner, ['name' => 'Bruno Costa', 'phone' => '21999990000']);

        $this->actingAs($owner)->get('/clientes?q='.urlencode('(11) 98765'))
            ->assertSee('Ana Paula')
            ->assertDontSee('Bruno Costa');
    }

    public function test_search_never_leaks_customers_from_other_tenants(): void
    {
        $owner = $this->owner();
        Customer::factory()->create(['name' => 'Ana de Outra Loja', 'phone' => '11987654321']);

        $this->actingAs($owner)->get('/clientes?q=ana')
            ->assertDontSee('Ana de Outra Loja');

        $this->actingAs($owner)->get('/clientes?q=11987654321')
            ->assertDontSee('Ana de Outra Loja');
    }

    // ---------- cadastro ----------

    public function test_user_can_create_customer_for_own_tenant(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)->post('/clientes', $this->validData());

        $customer = Customer::withoutGlobalScopes()->where('name', 'Maria Cliente')->firstOrFail();

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertSame('11987654321', $customer->phone);
        $this->assertSame('maria@example.com', $customer->email);
        $this->assertSame($owner->tenant_id, $customer->tenant_id);
    }

    public function test_technician_can_also_create_customers(): void
    {
        $technician = $this->technician($this->owner());

        $this->actingAs($technician)->post('/clientes', $this->validData())
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['name' => 'Maria Cliente']);
    }

    public function test_country_code_is_removed_from_phone(): void
    {
        $this->actingAs($this->owner())
            ->post('/clientes', $this->validData(['phone' => '+55 (11) 98765-4321']));

        $this->assertDatabaseHas('customers', ['phone' => '11987654321']);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post('/clientes', $this->validData(['phone' => '12345']))
            ->assertSessionHasErrors('phone');

        $this->assertDatabaseMissing('customers', ['name' => 'Maria Cliente']);
    }

    public function test_name_and_phone_are_required(): void
    {
        $this->actingAs($this->owner())
            ->post('/clientes', ['name' => '', 'phone' => ''])
            ->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_email_and_notes_are_optional(): void
    {
        $this->actingAs($this->owner())
            ->post('/clientes', $this->validData(['email' => '', 'notes' => '']))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('customers', ['name' => 'Maria Cliente', 'email' => null]);
    }

    public function test_tenant_id_sent_in_request_is_ignored(): void
    {
        $owner = $this->owner();
        $other = Tenant::factory()->create();

        $this->actingAs($owner)->post('/clientes', $this->validData(['tenant_id' => $other->id]));

        $customer = Customer::withoutGlobalScopes()->where('name', 'Maria Cliente')->firstOrFail();

        $this->assertSame($owner->tenant_id, $customer->tenant_id);
    }

    // ---------- edição ----------

    public function test_user_can_update_a_customer(): void
    {
        $owner = $this->owner();
        $customer = $this->customerOf($owner);

        $this->actingAs($owner)
            ->put("/clientes/{$customer->id}", $this->validData(['name' => 'Nome Novo']))
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame('Nome Novo', $customer->fresh()->name);
    }

    // ---------- isolamento ----------

    public function test_customer_from_another_tenant_is_not_found(): void
    {
        $owner = $this->owner();
        $stranger = Customer::factory()->create(['name' => 'Cliente Estranho']);

        $this->actingAs($owner);

        $this->get("/clientes/{$stranger->id}")->assertNotFound();
        $this->get("/clientes/{$stranger->id}/editar")->assertNotFound();
        $this->put("/clientes/{$stranger->id}", $this->validData(['name' => 'Invasor']))->assertNotFound();
        $this->delete("/clientes/{$stranger->id}")->assertNotFound();

        $this->assertSame('Cliente Estranho', $stranger->fresh()->name);
    }

    // ---------- remoção ----------

    public function test_only_owner_can_delete_customers(): void
    {
        $owner = $this->owner();
        $technician = $this->technician($owner);
        $customer = $this->customerOf($owner);

        $this->actingAs($technician)->delete("/clientes/{$customer->id}")->assertForbidden();
        $this->assertModelExists($customer);

        $this->actingAs($owner)->delete("/clientes/{$customer->id}")
            ->assertRedirect(route('customers.index'));
        $this->assertModelMissing($customer);
    }

    // ---------- model ----------

    public function test_phone_is_formatted_for_display(): void
    {
        $this->assertSame('(11) 98765-4321', (new Customer(['phone' => '11987654321']))->formattedPhone());
        $this->assertSame('(11) 3456-7890', (new Customer(['phone' => '1134567890']))->formattedPhone());
    }
}