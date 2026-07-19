<?php

namespace App\Enums;

enum CrmLeaveStatus: string
{
    case Approved = 'approved';
    case Planned = 'planned';
    case Pending = 'pending';
    case Refused = 'refused';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases(),
        );
    }

    public function label(): string
    {
        return trans('crm-leaves.statuses.'.$this->value, [], 'fr');
    }
}
