<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmEquipmentRentalUiAssetTest extends TestCase
{
    public function test_equipment_rental_module_uses_current_api_route(): void
    {
        $equipmentAsset = (string) file_get_contents(public_path('assets/equipment-rentals-Codex2.js'));
        $indexAsset = (string) file_get_contents(public_path('assets/index-CqSzWeas.js'));

        $this->assertStringContainsString('var API="/api/equipment-rentals"', $equipmentAsset);
        $this->assertStringNotContainsString('/api/equipment-rentals.php', $equipmentAsset);
        $this->assertStringContainsString('./equipment-rentals-Codex2.js?v=202607192230', $indexAsset);
        $this->assertStringNotContainsString('./equipment-rentals-Codex2.js?v=202607191940', $indexAsset);
    }
}
