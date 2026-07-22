<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmCheckRemittanceUiAssetTest extends TestCase
{
    public function test_check_remittance_module_keeps_primary_button_text_white(): void
    {
        $asset = (string) file_get_contents(base_path('Modules/CrmCheckRemittances/resources/assets/crm-remise-cheques.js'));

        $this->assertStringContainsString("const api = '/api/remise-cheques'", $asset);
        $this->assertStringNotContainsString('/api/remise-cheques.php', $asset);
        $this->assertStringContainsString('check-button check-button-primary" data-new-remittance', $asset);
        $this->assertStringContainsString('#crm-check-remittance-module .check-button-primary span{color:#fff;font-weight:600}', $asset);
    }
}
