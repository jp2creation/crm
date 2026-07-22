<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmEquipmentRentalUiAssetTest extends TestCase
{
    public function test_equipment_rental_module_uses_current_api_route(): void
    {
        $equipmentAsset = (string) file_get_contents(resource_path('frontend/static/assets/equipment-rentals-Codex2.js'));
        $hosts = (string) file_get_contents(resource_path('frontend/crm/modules/hosts.ts'));
        $modules = (string) file_get_contents(resource_path('frontend/crm/modules/register.ts'));

        $this->assertStringContainsString('var API="/api/equipment-rentals"', $equipmentAsset);
        $this->assertStringNotContainsString('/api/equipment-rentals.php', $equipmentAsset);
        $this->assertStringContainsString('Array.isArray(user.moduleIds)?user.moduleIds:[]', $equipmentAsset);
        $this->assertStringContainsString('Array.isArray(user.permissions)?user.permissions:[]', $equipmentAsset);
        $this->assertStringNotContainsString('user.moduleIds.includes', $equipmentAsset);
        $this->assertStringContainsString('state.data.user&&state.data.user.id===state.selectedUserId', $equipmentAsset);
        $this->assertStringContainsString("id: 'crm-equipment-rentals-module'", $hosts);
        $this->assertStringContainsString("paths: ['/locations-materiel']", $hosts);
        $this->assertStringContainsString("prefix: '/locations-materiel/'", $hosts);
        $this->assertStringContainsString("componentExport: 'EquipmentRentalsPage'", $modules);
        $this->assertStringContainsString("hostId: 'crm-equipment-rentals-module'", $modules);
        $this->assertStringContainsString("loadLegacyAsset('equipment-rentals-Codex2.js')", $modules);
        $this->assertStringNotContainsString('equipment-rentals-Codex2.js?v=202607191940', $modules);
    }
}
