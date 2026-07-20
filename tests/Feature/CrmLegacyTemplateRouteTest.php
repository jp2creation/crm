<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class CrmLegacyTemplateRouteTest extends TestCase
{
    public function test_legacy_template_pages_are_not_available(): void
    {
        foreach (['/auth/login', '/forms/layout', '/tables/simple', '/charts/line', '/pages/pricing'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_legacy_dashboard_path_redirects_to_crm_dashboard(): void
    {
        $this->actingAs(User::factory()->make())
            ->get('/dashboard')
            ->assertRedirect('/dashboard/crm');
    }
}
