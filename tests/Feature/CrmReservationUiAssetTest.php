<?php

namespace Tests\Feature;

use Tests\TestCase;

class CrmReservationUiAssetTest extends TestCase
{
    public function test_vehicle_day_view_uses_two_horizontal_time_rows(): void
    {
        $reservationAsset = (string) file_get_contents(public_path('assets/reservations-CSr_CND1.js'));
        $indexAsset = (string) file_get_contents(public_path('assets/index-CqSzWeas.js'));

        $this->assertStringContainsString('reservation-day-board', $reservationAsset);
        $this->assertStringContainsString('reservation-day-row-track-morning', $reservationAsset);
        $this->assertStringContainsString('reservation-day-row-track-afternoon', $reservationAsset);
        $this->assertStringContainsString('function ReservationDayRow', $reservationAsset);
        $this->assertStringContainsString('function reservationHorizontalPosition', $reservationAsset);
        $this->assertStringContainsString('function vehicleDaySlots', $reservationAsset);
        $this->assertStringContainsString('function vehiclePlannerBounds', $reservationAsset);
        $this->assertStringContainsString('dayStartTime', $reservationAsset);
        $this->assertStringContainsString('vehicleDefaultDayHours', $reservationAsset);
        $this->assertStringContainsString('morningStart: `06:00`', $reservationAsset);
        $this->assertStringContainsString('morningEnd: `12:30`', $reservationAsset);
        $this->assertStringContainsString('afternoonStart: `13:00`', $reservationAsset);
        $this->assertStringContainsString('afternoonEnd: `19:30`', $reservationAsset);
        $this->assertStringContainsString('Math.floor(timeMinutes(n.dayEnd) / 60) * 60', $reservationAsset);
        $this->assertStringContainsString('timeMinutes(vehicleDefaultDayHours.morningEnd)', $reservationAsset);
        $this->assertStringContainsString('[...new Set(s)].sort', $reservationAsset);
        $this->assertStringContainsString('function reservationCellIsSelected', $reservationAsset);
        $this->assertStringContainsString('function ReservationDaySelectionPanel', $reservationAsset);
        $this->assertStringContainsString('function openReservationRange', $reservationAsset);
        $this->assertStringContainsString('setDaySelection', $reservationAsset);
        $this->assertStringContainsString('reservation-day-cell-button', $reservationAsset);
        $this->assertStringContainsString('reservation-day-selection-confirm', $reservationAsset);
        $this->assertStringContainsString('reservation-day-selection-clear', $reservationAsset);
        $this->assertStringContainsString('is-selecting', $reservationAsset);
        $this->assertStringContainsString('Clique sur l heure de fin', $reservationAsset);
        $this->assertStringContainsString('Valide pour ouvrir la fiche', $reservationAsset);
        $this->assertStringContainsString('daySelection.startAt === e', $reservationAsset);
        $this->assertStringContainsString('Debut annule. Choisis une nouvelle heure de depart.', $reservationAsset);
        $this->assertStringNotContainsString('daySelection.period !== i', $reservationAsset);
        $this->assertStringContainsString('#16a34a', $reservationAsset);
        $this->assertStringContainsString('#dc2626', $reservationAsset);
        $this->assertStringContainsString('function ReservationAvailabilityLegend', $reservationAsset);
        $this->assertStringContainsString('function ReservationPeriodLegend', $reservationAsset);
        $this->assertStringContainsString('ReservationAvailabilityLegend : ReservationPeriodLegend', $reservationAsset);
        $this->assertStringContainsString('`Disponible`', $reservationAsset);
        $this->assertStringContainsString('`R\\u00e9serv\\u00e9`', $reservationAsset);
        $this->assertStringContainsString('`Matin`', $reservationAsset);
        $this->assertStringContainsString('`Apr\\u00e8s-midi`', $reservationAsset);
        $this->assertStringContainsString('`Journ\\u00e9e compl\\u00e8te`', $reservationAsset);
        $this->assertStringNotContainsString('className: `reservation-day-pills`', $reservationAsset);
        $this->assertStringContainsString('function ReservationMobileDaySlots', $reservationAsset);
        $this->assertStringContainsString('reservation-mobile-day-slots', $reservationAsset);
        $this->assertStringContainsString('reservation-mobile-slot-button', $reservationAsset);
        $this->assertStringContainsString('@media (max-width:560px)', $reservationAsset);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $reservationAsset);
        $this->assertStringContainsString('assets/reservations-CSr_CND1.js?v=2026071702', $indexAsset);
        $this->assertStringContainsString('./reservations-CSr_CND1.js?v=2026071702', $indexAsset);
        $this->assertStringNotContainsString('./reservations-CSr_CND1.js?v=2026071603', $indexAsset);
        $this->assertStringNotContainsString('./reservations-CSr_CND1.js?v=2026071602', $indexAsset);
        $this->assertStringNotContainsString('./reservations-CSr_CND1.js?v=2026071404', $indexAsset);
    }
}
