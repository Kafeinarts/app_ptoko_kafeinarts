<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_guest_can_see_login_page(): void
    {
        $this->seed();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('email');
    }

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_login_and_is_redirected_to_dashboard(): void
    {
        $this->seed();

        $response = $this->post(route('login.post'), [
            'email' => 'admin@kafeinarts.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'admin@kafeinarts.test')->firstOrFail());
    }

    public function test_kasir_can_login(): void
    {
        $this->seed();

        $response = $this->post(route('login.post'), [
            'email' => 'kasir@kafeinarts.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'kasir@kafeinarts.test')->firstOrFail());
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->seed();

        $this->post(route('login.post'), [
            'email' => 'admin@kafeinarts.test',
            'password' => 'salah-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->seededUser();
        $user->update(['is_active' => false]);

        $this->post(route('login.post'), [
            'email' => 'admin@kafeinarts.test',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAsSeeded();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
