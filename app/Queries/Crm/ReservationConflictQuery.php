<?php

namespace App\Queries\Crm;

use App\Models\CrmEquipmentRental;
use App\Models\CrmLeaveEntry;
use App\Models\CrmReservation;
use App\Services\Crm\LeavePeriodConflict;

class ReservationConflictQuery
{
    public function __construct(private readonly LeavePeriodConflict $leavePeriods) {}

    public function vehicleOverlaps(int $vehicleId, string $startAt, string $endAt, ?int $ignoreId = null): bool
    {
        return CrmReservation::query()
            ->where('vehicle_id', $vehicleId)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('end_at', '>', $startAt)
            ->where('start_at', '<', $endAt)
            ->lockForUpdate()
            ->exists();
    }

    public function equipmentOverlaps(int $itemId, string $startAt, string $endAt, ?int $ignoreId = null): bool
    {
        return CrmEquipmentRental::query()
            ->where('equipment_item_id', $itemId)
            ->where('status', '!=', CrmEquipmentRental::STATUS_CANCELLED)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('end_at', '>', $startAt)
            ->where('start_at', '<', $endAt)
            ->lockForUpdate()
            ->exists();
    }

    public function leaveOverlaps(int $employeeId, string $startDate, string $endDate, string $period, ?int $ignoreId = null): bool
    {
        return CrmLeaveEntry::query()
            ->where('employee_id', $employeeId)
            ->where('status', '!=', 'refused')
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereDate('end_date', '>=', $startDate)
            ->whereDate('start_date', '<=', $endDate)
            ->lockForUpdate()
            ->get(['start_date', 'end_date', 'period', 'status'])
            ->contains(fn (CrmLeaveEntry $entry): bool => $this->leavePeriods->overlaps(
                $startDate,
                $endDate,
                $period,
                (string) $entry->start_date,
                (string) $entry->end_date,
                $entry->period,
                $entry->status,
            ));
    }
}
