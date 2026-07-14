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
            ->assertOk();
    }

    public function test_authenticated_user_can_refresh_crm_dashboard_route(): void
    {
        $this->actingAs(User::factory()->make())
            ->get('/dashboard/crm')
            ->assertOk()
            ->assertViewIs('crm');
    }
}
