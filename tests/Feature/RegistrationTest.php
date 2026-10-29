<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'shop_name' => 'Celular Fix',
            'name' => 'Maria Souza',
            'email' => 'maria@example.com',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ], $overrides);
    }

    public function test_register_page_loads_for_guests(): void
    {
        $this->get('/cadastro')->assertOk()->assertSee('Criar conta');
    }

    public function test_registration_creates_tenant_and_owner_and_logs_in(): void
    {
        $response = $this->post('/cadastro', $this->validData());

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertSame(Role::Owner, $user->role);
        $this->assertSame('Celular Fix', $user->tenant->name);
        $this->assertSame('celular-fix', $user->tenant->slug);
    }

    public function test_duplicate_shop_names_get_unique_slugs(): void
    {
        Tenant::factory()->create(['name' => 'Celular Fix', 'slug' => 'celular-fix']);

        $this->post('/cadastro', $this->validData());

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertSame('celular-fix-2', $user->tenant->slug);
    }

    public function test_email_is_stored_in_lowercase(): void
    {
        $this->post('/cadastro', $this->validData(['email' => 'Maria@Example.COM']));

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $this->post('/cadastro', $this->validData())
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_short_password_is_rejected(): void
    {
        $this->post('/cadastro', $this->validData([
            'password' => '123',
            'password_confirmation' => '123',
        ]))->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post('/cadastro', $this->validData([
            'password_confirmation' => 'outra-senha-123',
        ]))->assertSessionHasErrors('password');
    }

    public function test_registration_ignores_role_and_tenant_sent_in_request(): void
    {
        $other = Tenant::factory()->create();

        $this->post('/cadastro', $this->validData([
            'role' => 'attendant',
            'tenant_id' => $other->id,
        ]));

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertSame(Role::Owner, $user->role);
        $this->assertNotSame($other->id, $user->tenant_id);
    }
}