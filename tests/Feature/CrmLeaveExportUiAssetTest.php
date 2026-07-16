<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmLeaveExportUiAssetTest extends TestCase
{
    public function test_leave_export_uses_a_monthly_spreadsheet_layout(): void
    {
        $source = (string) file_get_contents(base_path('Modules/CrmLeaves/resources/assets/crm-conges.js'));
        $public = (string) file_get_contents(public_path('modules/crm-leaves/crm-conges.js'));

        $this->assertSame($source, $public);
        $this->assertStringContainsString('Tableau des congés et absences', $public);
        $this->assertStringContainsString('class="pdf-planning-table"', $public);
        $this->assertStringContainsString('class="pdf-month-band"', $public);
        $this->assertStringContainsString('class="pdf-employee-cell"', $public);
        $this->assertStringContainsString('class="pdf-absence-chip', $public);
        $this->assertStringContainsString('function renderExportPlanning', $public);
        $this->assertStringContainsString('function leaveTypeCode', $public);
        $this->assertStringNotContainsString('class="pdf-calendar"', $public);
        $this->assertStringNotContainsString('function renderExportRows', $public);
    }
}
