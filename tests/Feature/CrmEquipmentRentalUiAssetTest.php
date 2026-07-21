<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmEquipmentRentalUiAssetTest extends TestCase
{
    public function test_equipment_rental_module_uses_current_api_route(): void
    {
        $equipmentAsset = (string) file_get_contents(resource_path('frontend/static/assets/equipment-rentals-Codex2.js'));
        $indexAsset = (string) file_get_contents(resource_path('frontend/static/assets/index-CqSzWeas.js'));

        $this->assertStringContainsString('var API="/api/equipment-rentals"', $equipmentAsset);
        $this->assertStringNotContainsString('/api/equipment-rentals.php', $equipmentAsset);
        $this->assertStringContainsString('Array.isArray(user.moduleIds)?user.moduleIds:[]', $equipmentAsset);
        $this->assertStringContainsString('Array.isArray(user.permissions)?user.permissions:[]', $equipmentAsset);
        $this->assertStringNotContainsString('user.moduleIds.includes', $equipmentAsset);
        $this->assertStringContainsString('state.data.user&&state.data.user.id===state.selectedUserId', $equipmentAsset);
        $this->assertStringContainsString('./equipment-rentals-Codex2.js?v=202607201920', $indexAsset);
        $this->assertStringContainsString('path:`locations-materiel`,element:$(equipy)', $indexAsset);
        $this->assertStringContainsString('path:`locations-materiel/*`,element:$(equipy)', $indexAsset);
        $this->assertStringContainsString('path:`location-materiel`,element:(0,z.jsx)(Rr,{to:`/locations-materiel`,replace:!0})', $indexAsset);
        $this->assertStringNotContainsString('./equipment-rentals-Codex2.js?v=202607191940', $indexAsset);
    }
}
