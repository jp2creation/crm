<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmLegacyDashboardRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_dashboard_crm_module_path_redirects_to_absolute_module_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard/crm/equipes')
            ->assertRedirect('/equipes');
    }

    public function test_legacy_dashboard_relative_module_path_redirects_to_absolute_module_route(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard/equipes?siteId=2')
            ->assertRedirect('/equipes?siteId=2');
    }
}
