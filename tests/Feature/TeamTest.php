<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->create();
    }

    private function memberOf(User $owner, Role $role = Role::Technician): User
    {
        return User::factory()->create(['tenant_id' => $owner->tenant_id, 'role' => $role]);
    }

    private function newMemberData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'João Técnico',
            'email' => 'joao@example.com',
            'role' => 'technician',
            'password' => 'senha-segura-123',
            'password_confirmation' => 'senha-segura-123',
        ], $overrides);
    }

    // ---------- acesso ----------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/equipe')->assertRedirect(route('login'));
    }

    public function test_non_owner_cannot_access_the_team_area(): void
    {
        $technician = $this->memberOf($this->owner());

        $this->actingAs($technician)->get('/equipe')->assertForbidden();
        $this->actingAs($technician)->get('/equipe/novo')->assertForbidden();
        $this->actingAs($technician)->post('/equipe', $this->newMemberData())->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);
    }

    public function test_team_link_is_shown_only_to_the_owner(): void
    {
        $owner = $this->owner();
        $technician = $this->memberOf($owner);

        $this->actingAs($owner)->get('/')->assertSee('Equipe');
        $this->actingAs($technician)->get('/')->assertDontSee('Equipe');
    }

    // ---------- listagem ----------

    public function test_owner_sees_only_members_of_own_tenant(): void
    {
        $owner = $this->owner();
        $mine = $this->memberOf($owner);
        User::factory()->create(['name' => 'Pessoa de Outra Loja']);

        $this->actingAs($owner)->get('/equipe')
            ->assertOk()
            ->assertSee($mine->name)
            ->assertDontSee('Pessoa de Outra Loja');
    }

    // ---------- criar ----------

    public function test_owner_can_add_a_member_to_own_tenant(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post('/equipe', $this->newMemberData(['email' => 'Joao@Example.com']))
            ->assertRedirect(route('team.index'));

        $member = User::where('email', 'joao@example.com')->firstOrFail();

        $this->assertSame($owner->tenant_id, $member->tenant_id);
        $this->assertSame(Role::Technician, $member->role);
        $this->assertTrue(Hash::check('senha-segura-123', $member->password));
    }

    public function test_owner_cannot_create_another_owner(): void
    {
        $this->actingAs($this->owner())
            ->post('/equipe', $this->newMemberData(['role' => 'owner']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'joao@example.com']);
    }

    public function test_member_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'joao@example.com']);

        $this->actingAs($this->owner())
            ->post('/equipe', $this->newMemberData())
            ->assertSessionHasErrors('email');
    }

    // ---------- editar ----------

    public function test_owner_can_update_a_member(): void
    {
        $owner = $this->owner();
        $member = $this->memberOf($owner, Role::Technician);

        $this->actingAs($owner)->put("/equipe/{$member->id}", [
            'name' => 'Nome Novo',
            'role' => 'attendant',
        ])->assertRedirect(route('team.index'));

        $member->refresh();

        $this->assertSame('Nome Novo', $member->name);
        $this->assertSame(Role::Attendant, $member->role);
    }

    public function test_blank_password_keeps_the_current_password(): void
    {
        $owner = $this->owner();
        $member = $this->memberOf($owner);
        $member->update(['password' => 'senha-antiga-123']);

        $this->actingAs($owner)->put("/equipe/{$member->id}", [
            'name' => $member->name,
            'role' => 'technician',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $this->assertTrue(Hash::check('senha-antiga-123', $member->fresh()->password));
    }

    public function test_filled_password_replaces_the_current_one(): void
    {
        $owner = $this->owner();
        $member = $this->memberOf($owner);

        $this->actingAs($owner)->put("/equipe/{$member->id}", [
            'name' => $member->name,
            'role' => 'technician',
            'password' => 'senha-nova-123',
            'password_confirmation' => 'senha-nova-123',
        ]);

        $this->assertTrue(Hash::check('senha-nova-123', $member->fresh()->password));
    }

    public function test_owner_cannot_edit_a_member_from_another_tenant(): void
    {
        $stranger = User::factory()->create(['role' => Role::Technician]);

        $owner = $this->owner();

        $this->actingAs($owner)->get("/equipe/{$stranger->id}/editar")->assertNotFound();
        $this->actingAs($owner)->put("/equipe/{$stranger->id}", [
            'name' => 'Invasor',
            'role' => 'attendant',
        ])->assertNotFound();

        $this->assertNotSame('Invasor', $stranger->fresh()->name);
    }

    public function test_owner_account_cannot_be_edited_through_team_screen(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get("/equipe/{$owner->id}/editar")->assertForbidden();
    }

    // ---------- remover ----------

    public function test_owner_can_remove_a_member(): void
    {
        $owner = $this->owner();
        $member = $this->memberOf($owner);

        $this->actingAs($owner)->delete("/equipe/{$member->id}")
            ->assertRedirect(route('team.index'));

        $this->assertModelMissing($member);
    }

    public function test_owner_cannot_remove_a_member_from_another_tenant(): void
    {
        $stranger = User::factory()->create(['role' => Role::Technician]);

        $this->actingAs($this->owner())->delete("/equipe/{$stranger->id}")->assertNotFound();

        $this->assertModelExists($stranger);
    }

    public function test_owner_cannot_remove_own_account(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->delete("/equipe/{$owner->id}")->assertForbidden();

        $this->assertModelExists($owner);
    }
}