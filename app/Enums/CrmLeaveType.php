<?php

namespace App\Enums;

enum CrmLeaveType: string
{
    case PaidLeave = 'conge';
    case Rtt = 'rtt';
    case Absence = 'absence';
    case Training = 'formation';
    case SickLeave = 'maladie';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases(),
        );
    }

    public function label(): string
    {
        return trans('crm-leaves.types.'.$this->value, [], 'fr');
    }
}
