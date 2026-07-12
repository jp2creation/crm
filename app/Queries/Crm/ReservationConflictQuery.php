<?php

namespace App\Queries\Crm;

use App\Models\CrmEquipmentRental;
use App\Models\CrmLeaveEntry;
use App\Models\CrmReservation;

class ReservationConflictQuery
{
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

    public function leaveOverlaps(int $employeeId, string $startDate, string $endDate, ?int $ignoreId = null): bool
    {
        return CrmLeaveEntry::query()
            ->where('employee_id', $employeeId)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('end_date', '>=', $startDate)
            ->where('start_date', '<=', $endDate)
            ->lockForUpdate()
            ->exists();
    }
}
