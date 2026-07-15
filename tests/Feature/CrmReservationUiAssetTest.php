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
        $this->assertStringContainsString('function ReservationMobileDaySlots', $reservationAsset);
        $this->assertStringContainsString('reservation-mobile-day-slots', $reservationAsset);
        $this->assertStringContainsString('reservation-mobile-slot-button', $reservationAsset);
        $this->assertStringContainsString('@media (max-width:560px)', $reservationAsset);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $reservationAsset);
        $this->assertStringContainsString('assets/reservations-CSr_CND1.js?v=2026071416', $indexAsset);
        $this->assertStringContainsString('./reservations-CSr_CND1.js?v=2026071416', $indexAsset);
        $this->assertStringNotContainsString('./reservations-CSr_CND1.js?v=2026071404', $indexAsset);
    }
}
