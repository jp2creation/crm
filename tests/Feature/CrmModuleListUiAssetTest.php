<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmModuleListUiAssetTest extends TestCase
{
    public function test_sales_visits_are_rendered_as_a_real_table(): void
    {
        $source = (string) file_get_contents(base_path('Modules/CrmSalesTours/resources/assets/crm-tournees-representants.js'));
        $public = (string) file_get_contents(public_path('modules/crm-sales-tours/crm-tournees-representants.js'));

        $this->assertSame($source, $public);
        $this->assertStringContainsString('class="visit-table-wrap"', $public);
        $this->assertStringContainsString('<table class="visit-table">', $public);
        $this->assertStringContainsString('<th>Client</th>', $public);
        $this->assertStringContainsString('<th>Contact</th>', $public);
        $this->assertStringContainsString('<th>Suite</th>', $public);
        $this->assertStringContainsString('class="visit-chip"', $public);
        $this->assertStringNotContainsString('class="visit-list"', $public);
        $this->assertStringNotContainsString('class="visit-card"', $public);
    }

    public function test_team_members_are_rendered_as_a_column_list_on_mobile_too(): void
    {
        $source = (string) file_get_contents(base_path('Modules/CrmTeams/resources/assets/crm-equipes.js'));
        $public = (string) file_get_contents(public_path('modules/crm-teams/crm-equipes.js'));

        $this->assertSame($source, $public);
        $this->assertStringContainsString('class="teams-table-wrap"', $public);
        $this->assertStringContainsString('<table class="teams-table">', $public);
        $this->assertStringContainsString('<th>Rôle</th>', $public);
        $this->assertStringContainsString('class="teams-role-pill"', $public);
        $this->assertStringContainsString('.teams-table{min-width:54rem}', $public);
        $this->assertStringNotContainsString('teams-mobile-list', $public);
        $this->assertStringNotContainsString('function renderMemberCard', $public);
        $this->assertStringNotContainsString('teams-person-row', $public);
    }
}
