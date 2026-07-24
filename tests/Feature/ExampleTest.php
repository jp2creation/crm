<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_redirected_to_login_from_crm_home(): void
    {
        $this->get('/')
            ->assertRedirect('/login');
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Installer Martin Sols')
            ->assertSee('APK Android')
            ->assertDontSee('Connexion équipe')
            ->assertDontSee('Sécurité anti-robot')
            ->assertDontSee('Connexion CRM');
    }

    public function test_login_page_can_remember_the_user_email_without_storing_the_password(): void
    {
        $html = $this->get('/login')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('autocomplete="on" data-login-form', $html);
        $this->assertStringContainsString('autocomplete="username"', $html);
        $this->assertStringContainsString('autocomplete="current-password"', $html);
        $this->assertStringContainsString('data-login-email', $html);
        $this->assertStringContainsString('data-login-remember checked', $html);
        $this->assertStringContainsString('martin-sols:login:remembered-email', $html);
        $this->assertStringContainsString('window.localStorage.setItem(storageKey, normalizedEmail)', $html);
        $this->assertStringContainsString('window.localStorage.removeItem(storageKey)', $html);
        $this->assertStringContainsString('Rester connect', $html);
        $this->assertStringNotContainsString('connectÃ', $html);
        $this->assertStringNotContainsString('localStorage.setItem(storageKey, password', $html);
    }

    public function test_authenticated_user_can_refresh_legacy_crm_dashboard_route(): void
    {
        $this->actingAs(User::factory()->make())
            ->get('/dashboard/crm')
            ->assertRedirect('/');

        $this->actingAs(User::factory()->make())
            ->followingRedirects()
            ->get('/dashboard/crm')
            ->assertOk()
            ->assertViewIs('crm');
    }
}
