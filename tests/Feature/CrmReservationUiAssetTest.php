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
        $this->assertStringContainsString('function ReservationModuleLoading', $reservationAsset);
        $this->assertStringContainsString('Chargement du module Sprinter', $reservationAsset);
        $this->assertStringContainsString('Chargement des vehicules', $reservationAsset);
        $this->assertStringContainsString('Connexion aux donnees MySQL du site actif...', $reservationAsset);
        $this->assertStringContainsString('Connexion aux donnees reservations indisponible', $reservationAsset);
        $this->assertStringContainsString('v = `/api/reservations`', $reservationAsset);
        $this->assertStringNotContainsString('/api/reservations.php', $reservationAsset);
        $this->assertStringContainsString('Array.isArray(e.moduleIds) ? e.moduleIds : []', $reservationAsset);
        $this->assertStringContainsString('Array.isArray(e.permissions) ? e.permissions : []', $reservationAsset);
        $this->assertStringNotContainsString('e.moduleIds.includes', $reservationAsset);
        $this->assertStringContainsString('...n.users.filter(', $reservationAsset);
        $this->assertStringContainsString('(e) => e.id !== n.user.id', $reservationAsset);
        $this->assertStringContainsString('sites: []', $reservationAsset);
        $this->assertStringContainsString('vehicles: []', $reservationAsset);
        $this->assertStringContainsString('reservations: []', $reservationAsset);
        $this->assertStringNotContainsString('Module Sprinter dans le CRM Martin Sols', $reservationAsset);
        $this->assertStringNotContainsString('vehicles: ne', $reservationAsset);
        $this->assertStringNotContainsString('reservations: re', $reservationAsset);
        $this->assertStringContainsString('function todayDate', $reservationAsset);
        $this->assertStringContainsString('onToday: () => {', $reservationAsset);
        $this->assertStringContainsString('a(todayDate())', $reservationAsset);
        $this->assertStringContainsString('i(`day`)', $reservationAsset);
        $this->assertStringContainsString('[le, C] = (0, _.useState)(() => todayDate())', $reservationAsset);
        $this->assertStringNotContainsString('reservation-month-cell-today', $reservationAsset);
        $this->assertStringNotContainsString('"aria-current": c ? `date` : void 0', $reservationAsset);
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
        $this->assertStringContainsString('function reservationSelectionCellLabel', $reservationAsset);
        $this->assertStringContainsString('function ReservationDaySelectionPanel', $reservationAsset);
        $this->assertStringContainsString('function openReservationRange', $reservationAsset);
        $this->assertStringContainsString('function canDeleteReservation', $reservationAsset);
        $this->assertStringContainsString('setDaySelection', $reservationAsset);
        $this->assertStringContainsString('reservation-day-cell-button', $reservationAsset);
        $this->assertStringContainsString('reservation-day-selection-confirm', $reservationAsset);
        $this->assertStringContainsString('reservation-day-selection-clear', $reservationAsset);
        $this->assertStringContainsString('is-selecting', $reservationAsset);
        $this->assertStringContainsString('Clique sur l heure de fin', $reservationAsset);
        $this->assertStringContainsString('Valide pour ouvrir la fiche', $reservationAsset);
        $this->assertStringContainsString('daySelection.startAt === e', $reservationAsset);
        $this->assertStringContainsString('Debut annule. Choisis une nouvelle heure de depart.', $reservationAsset);
        $this->assertStringContainsString('Fin choisie', $reservationAsset);
        $this->assertStringContainsString('S\\u00e9lectionn\\u00e9', $reservationAsset);
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
        $this->assertStringContainsString('.reservation-day-board{display:none', $reservationAsset);
        $this->assertStringContainsString('.reservation-mobile-slot-column{display:grid;min-width:0;grid-template-columns:7.2rem repeat(7,minmax(0,1fr))', $reservationAsset);
        $this->assertStringContainsString('grid-auto-rows:minmax(2.38rem,auto)', $reservationAsset);
        $this->assertStringContainsString('grid-row:1 / span 2', $reservationAsset);
        $this->assertStringContainsString('max-h-[82vh] w-full max-w-lg', $reservationAsset);
        $this->assertStringContainsString('className: `space-y-3`', $reservationAsset);
        $this->assertStringContainsString('rows: 2', $reservationAsset);
        $this->assertStringContainsString('reservation-fast-summary', $reservationAsset);
        $this->assertStringContainsString('summaryDateFormat = new Intl.DateTimeFormat', $reservationAsset);
        $this->assertStringContainsString('function reservationSummaryDateTime', $reservationAsset);
        $this->assertStringContainsString('reservation-fast-summary-datetime', $reservationAsset);
        $this->assertStringContainsString('reservation-fast-summary-date', $reservationAsset);
        $this->assertStringContainsString('reservation-fast-summary-time', $reservationAsset);
        $this->assertStringContainsString('y = reservationSummaryDateTime(t.startAt)', $reservationAsset);
        $this->assertStringContainsString('b = reservationSummaryDateTime(t.endAt)', $reservationAsset);
        $this->assertStringContainsString('.reservation-fast-summary-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:.42rem}', $reservationAsset);
        $this->assertStringContainsString('.reservation-fast-summary-item:first-child{grid-column:1/-1}', $reservationAsset);
        $this->assertStringContainsString('.reservation-fast-summary-user{grid-column:1/-1}', $reservationAsset);
        $this->assertStringContainsString('R\\u00e9sum\\u00e9 de la r\\u00e9servation', $reservationAsset);
        $this->assertStringContainsString('R\\u00e9serv\\u00e9 par', $reservationAsset);
        $this->assertStringContainsString('activeUser: G', $reservationAsset);
        $this->assertStringContainsString('userName: G.name', $reservationAsset);
        $this->assertStringContainsString('userName: e.userName', $reservationAsset);
        $this->assertStringContainsString('reservation-fast-actions grid grid-cols-2 gap-2', $reservationAsset);
        $this->assertStringContainsString('? `Modifier`', $reservationAsset);
        $this->assertStringContainsString('`Supprimer`', $reservationAsset);
        $this->assertStringContainsString('canDelete: De ? canDeleteReservation(K, G, De) : !1', $reservationAsset);
        $this->assertStringNotContainsString('Formulaire Sprinter branche sur le planning CRM.', $reservationAsset);
        $this->assertStringNotContainsString('document.getElementById(`reservation-notes`)?.focus()', $reservationAsset);
        $this->assertStringNotContainsString('id: `reservation-title`', $reservationAsset);
        $this->assertStringNotContainsString('id: `reservation-phone`', $reservationAsset);
        $this->assertStringNotContainsString('id: `reservation-start`', $reservationAsset);
        $this->assertStringNotContainsString('id: `reservation-end`', $reservationAsset);
        $this->assertStringContainsString('reservation-notes', $reservationAsset);
        $this->assertStringContainsString('@media (max-width:560px)', $reservationAsset);
        $this->assertStringContainsString('grid-template-columns:repeat(2,minmax(0,1fr))', $reservationAsset);
        $this->assertStringContainsString('assets/reservations-CSr_CND1.js?v=202607201920', $indexAsset);
        $this->assertStringContainsString('./reservations-CSr_CND1.js?v=202607201920', $indexAsset);
        $this->assertStringContainsString('path:`reservations`,element:$(py)', $indexAsset);
        $this->assertStringContainsString('path:`reservations/*`,element:$(py)', $indexAsset);
        $this->assertStringContainsString('path:`reservation`,element:(0,z.jsx)(Rr,{to:`/reservations`,replace:!0})', $indexAsset);
        foreach ([
            '2026071404',
            '2026071602',
            '2026071603',
            '2026071702',
            '2026071704',
            '2026071705',
            '2026071706',
            '2026071707',
            '2026071708',
            '2026071709',
            '2026071710',
            '2026071711',
            '2026071712',
            '2026071713',
            '202607191940',
        ] as $legacyVersion) {
            $this->assertStringNotContainsString('assets/reservations-CSr_CND1.js?v='.$legacyVersion, $indexAsset);
            $this->assertStringNotContainsString('./reservations-CSr_CND1.js?v='.$legacyVersion, $indexAsset);
        }
    }
}
