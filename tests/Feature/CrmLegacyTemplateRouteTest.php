<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class CrmLegacyTemplateRouteTest extends TestCase
{
    public function test_legacy_template_pages_redirect_guests_to_login(): void
    {
        foreach (['/auth/login', '/forms/layout', '/tables/simple', '/charts/line', '/pages/pricing'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_legacy_template_pages_redirect_authenticated_users_to_crm_dashboard(): void
    {
        foreach (['/app/ecommerce/products', '/features/query-builder', '/forms/layout', '/tables/simple', '/charts/line', '/pages/pricing'] as $path) {
            $this->actingAs(User::factory()->make())
                ->get($path)
                ->assertRedirect('/');
        }
    }

    public function test_legacy_dashboard_path_redirects_to_crm_dashboard(): void
    {
        $this->actingAs(User::factory()->make())
            ->get('/dashboard')
            ->assertRedirect('/');
    }
}
