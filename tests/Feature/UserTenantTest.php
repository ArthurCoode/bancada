<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_belongs_to_a_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $this->assertTrue($user->tenant->is($tenant));
        $this->assertTrue($tenant->users->contains($user));
    }

    public function test_role_is_cast_to_enum(): void
    {
        $user = User::factory()->create(['role' => Role::Technician]);

        $this->assertSame(Role::Technician, $user->fresh()->role);
        $this->assertFalse($user->isOwner());
    }

    public function test_tenant_id_and_role_are_not_mass_assignable(): void
    {
        $tenant = Tenant::factory()->create();

        $user = new User([
            'name' => 'Fulano',
            'email' => 'fulano@example.com',
            'password' => 'segredo123',
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $this->assertNull($user->tenant_id);
        $this->assertNull($user->role);
    }
}