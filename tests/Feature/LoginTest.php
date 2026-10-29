<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads_for_guests(): void
    {
        $this->get('/entrar')->assertOk()->assertSee('Entrar');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/entrar')->assertRedirect(route('home'));
    }

    public function test_user_can_login_with_email_in_any_case(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@example.com',
            'password' => 'senha-segura-123',
        ]);

        $this->post('/entrar', [
            'email' => 'ANA@example.com',
            'password' => 'senha-segura-123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_generic_error(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'senha-segura-123']);

        $this->post('/entrar', [
            'email' => 'ana@example.com',
            'password' => 'errada',
        ])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);

        $this->assertGuest();
    }

    public function test_unknown_email_shows_the_same_generic_error(): void
    {
        $this->post('/entrar', [
            'email' => 'ninguem@example.com',
            'password' => 'qualquer-coisa',
        ])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
    }

    public function test_login_is_blocked_after_too_many_attempts(): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'senha-segura-123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/entrar', ['email' => 'ana@example.com', 'password' => 'errada']);
        }

        // Mesmo com a senha CORRETA, a 6ª tentativa é bloqueada.
        $response = $this->post('/entrar', [
            'email' => 'ana@example.com',
            'password' => 'senha-segura-123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/sair')->assertRedirect(route('login'));

        $this->assertGuest();
    }
}