<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmReservationUiAssetTest extends TestCase
{
    public function test_vehicle_reservation_module_is_native_and_uses_current_api_route(): void
    {
        $reservationAsset = (string) file_get_contents(base_path('Modules/CrmReservations/resources/assets/crm-reservations.js'));
        $hosts = (string) file_get_contents(resource_path('frontend/crm/modules/hosts.ts'));
        $modules = (string) file_get_contents(resource_path('frontend/crm/modules/register.ts'));

        $this->assertStringContainsString('const api = "/api/reservations"', $reservationAsset);
        $this->assertStringNotContainsString('/api/reservations.php', $reservationAsset);
        $this->assertStringContainsString('credentials: "same-origin"', $reservationAsset);
        $this->assertStringContainsString('"X-CSRF-TOKEN": csrfToken()', $reservationAsset);
        $this->assertStringContainsString('create_reservation', $reservationAsset);
        $this->assertStringContainsString('update_reservation', $reservationAsset);
        $this->assertStringContainsString('delete_reservation', $reservationAsset);
        $this->assertStringContainsString('canDeleteReservation', $reservationAsset);
        $this->assertStringContainsString('reservations.delete_own', $reservationAsset);
        $this->assertStringContainsString('reservations.delete_any', $reservationAsset);
        $this->assertStringContainsString('reservation-day-board', $reservationAsset);
        $this->assertStringContainsString('reservation-mobile-slot-button', $reservationAsset);
        $this->assertStringContainsString('reservation-day-cell-button', $reservationAsset);
        $this->assertStringContainsString('reservation-day-row-track-morning', $reservationAsset);
        $this->assertStringContainsString('reservation-day-row-track-afternoon', $reservationAsset);
        $this->assertStringContainsString('vehicleDaySlots', $reservationAsset);
        $this->assertStringContainsString('vehicleDefaultDayHours', $reservationAsset);
        $this->assertStringContainsString('dayStartTime', $reservationAsset);
        $this->assertStringContainsString('dayEndTime', $reservationAsset);
        $this->assertStringContainsString('reservationCellIsSelected', $reservationAsset);
        $this->assertStringContainsString('reservationSelectionCellLabel', $reservationAsset);
        $this->assertStringContainsString('Début choisi', $reservationAsset);
        $this->assertStringContainsString('Fin choisie', $reservationAsset);
        $this->assertStringContainsString('Sélectionné', $reservationAsset);
        $this->assertStringContainsString('#16a34a', $reservationAsset);
        $this->assertStringContainsString('#dc2626', $reservationAsset);
        $this->assertStringContainsString('Disponible', $reservationAsset);
        $this->assertStringContainsString('Réservé', $reservationAsset);
        $this->assertStringContainsString('Matin', $reservationAsset);
        $this->assertStringContainsString('Après-midi', $reservationAsset);
        $this->assertStringContainsString('reservation-fast-actions', $reservationAsset);
        $this->assertStringContainsString('grid-template-columns:1fr 1fr', $reservationAsset);
        $this->assertStringContainsString('data-delete-reservation', $reservationAsset);
        $this->assertStringContainsString('data-resa-see-all', $reservationAsset);
        $this->assertStringContainsString('document.readyState ===', $reservationAsset);

        $this->assertStringContainsString("id: 'crm-reservations-module'", $hosts);
        $this->assertStringContainsString("paths: ['/reservations']", $hosts);
        $this->assertStringContainsString("prefix: '/reservations/'", $hosts);
        $this->assertStringContainsString("reservations: () => import('../../../../Modules/CrmReservations/resources/assets/crm-reservations.js')", $modules);
        $this->assertStringNotContainsString("loadLegacyAsset('reservations-CSr_CND1.js')", $modules);
        $this->assertFileDoesNotExist(resource_path('frontend/static/assets/reservations-CSr_CND1.js'));
    }
}
