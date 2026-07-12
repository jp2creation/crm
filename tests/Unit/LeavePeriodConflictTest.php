<?php

namespace Tests\Unit;

use App\Services\Crm\LeavePeriodConflict;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LeavePeriodConflictTest extends TestCase
{
    #[DataProvider('overlapCases')]
    public function test_it_detects_leave_period_conflicts(
        string $startDate,
        string $endDate,
        string $period,
        string $existingStartDate,
        string $existingEndDate,
        ?string $existingPeriod,
        string $existingStatus,
        bool $expected,
    ): void {
        $conflicts = new LeavePeriodConflict;

        $this->assertSame(
            $expected,
            $conflicts->overlaps(
                $startDate,
                $endDate,
                $period,
                $existingStartDate,
                $existingEndDate,
                $existingPeriod,
                $existingStatus,
            ),
        );
    }

    /**
     * @return iterable<string, array{string, string, string, string, string, ?string, string, bool}>
     */
    public static function overlapCases(): iterable
    {
        yield 'same morning conflicts' => [
            '2026-08-10',
            '2026-08-10',
            'morning',
            '2026-08-10',
            '2026-08-10',
            'morning',
            'approved',
            true,
        ];

        yield 'opposite half days are allowed' => [
            '2026-08-10',
            '2026-08-10',
            'morning',
            '2026-08-10',
            '2026-08-10',
            'afternoon',
            'approved',
            false,
        ];

        yield 'full day blocks a half day' => [
            '2026-08-10',
            '2026-08-10',
            'morning',
            '2026-08-10',
            '2026-08-10',
            'full',
            'approved',
            true,
        ];

        yield 'multi day overlap conflicts' => [
            '2026-08-10',
            '2026-08-12',
            'full',
            '2026-08-12',
            '2026-08-12',
            'afternoon',
            'approved',
            true,
        ];

        yield 'refused leave is ignored' => [
            '2026-08-10',
            '2026-08-10',
            'full',
            '2026-08-10',
            '2026-08-10',
            'full',
            'refused',
            false,
        ];

        yield 'separate dates are allowed' => [
            '2026-08-10',
            '2026-08-10',
            'full',
            '2026-08-11',
            '2026-08-11',
            'full',
            'approved',
            false,
        ];
    }
}
