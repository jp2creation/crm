<?php

namespace App\Enums;

enum CrmLeavePeriod: string
{
    case Full = 'full';
    case Morning = 'morning';
    case Afternoon = 'afternoon';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $period): string => $period->value,
            self::cases(),
        );
    }

    public function isHalfDay(): bool
    {
        return $this !== self::Full;
    }

    public function label(): string
    {
        return trans('crm-leaves.periods.'.$this->value, [], 'fr');
    }
}
