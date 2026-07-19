<?php

use App\Enums\CrmLeavePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_leave_entries')) {
            Schema::table('crm_leave_entries', function (Blueprint $table): void {
                if (! Schema::hasColumn('crm_leave_entries', 'duration_days')) {
                    $table->decimal('duration_days', 6, 2)->default(0)->after('period');
                }
            });

            $this->addIndex(
                'crm_leave_entries',
                'crm_leave_entries_employee_status_start_idx',
                ['employee_id', 'status', 'start_date'],
            );

            $this->backfillDurations();
        }

        if (! Schema::hasTable('crm_leave_balances')) {
            Schema::create('crm_leave_balances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('crm_leave_employees')->cascadeOnDelete();
                $table->string('type', 40)->default('conge');
                $table->unsignedSmallInteger('year');
                $table->decimal('entitled_days', 6, 2)->default(25);
                $table->decimal('carried_over_days', 6, 2)->default(0);
                $table->decimal('used_days', 6, 2)->default(0);
                $table->decimal('pending_days', 6, 2)->default(0);
                $table->decimal('remaining_days', 6, 2)->default(25);
                $table->timestamp('recalculated_at')->nullable();
                $table->timestamps();

                $table->unique(['employee_id', 'type', 'year'], 'crm_leave_balances_employee_type_year_unique');
                $table->index(['year', 'type']);
            });
        }

        if (! Schema::hasTable('crm_leave_transactions')) {
            Schema::create('crm_leave_transactions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('entry_id')->nullable()->index();
                $table->unsignedBigInteger('employee_id')->index();
                $table->string('type', 40)->default('conge');
                $table->unsignedSmallInteger('year');
                $table->decimal('amount_days', 6, 2);
                $table->decimal('balance_after', 6, 2)->nullable();
                $table->string('reason', 120)->default('');
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['employee_id', 'type', 'year'], 'crm_leave_transactions_employee_type_year_idx');
            });
        }

        if (! Schema::hasTable('crm_leave_status_histories')) {
            Schema::create('crm_leave_status_histories', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('entry_id')->index();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->unsignedBigInteger('changed_by')->nullable()->index();
                $table->text('reason')->nullable();
                $table->timestamp('changed_at')->useCurrent();
                $table->timestamps();

                $table->index(['to_status', 'changed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leave_status_histories');
        Schema::dropIfExists('crm_leave_transactions');
        Schema::dropIfExists('crm_leave_balances');

        $this->dropIndex('crm_leave_entries', 'crm_leave_entries_employee_status_start_idx');

        if (Schema::hasTable('crm_leave_entries') && Schema::hasColumn('crm_leave_entries', 'duration_days')) {
            Schema::table('crm_leave_entries', function (Blueprint $table): void {
                $table->dropColumn('duration_days');
            });
        }
    }

    private function backfillDurations(): void
    {
        DB::table('crm_leave_entries')
            ->select(['id', 'start_date', 'end_date', 'period'])
            ->orderBy('id')
            ->chunkById(200, function ($entries): void {
                foreach ($entries as $entry) {
                    DB::table('crm_leave_entries')
                        ->where('id', $entry->id)
                        ->update([
                            'duration_days' => $this->durationDays(
                                (string) $entry->start_date,
                                (string) $entry->end_date,
                                (string) ($entry->period ?: CrmLeavePeriod::Full->value),
                            ),
                        ]);
                }
            });
    }

    private function durationDays(string $startDate, string $endDate, string $period): float
    {
        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->startOfDay();

        if ($end->lessThan($start)) {
            return 0.0;
        }

        $days = (float) ($start->diffInDays($end) + 1);

        if ($period !== CrmLeavePeriod::Full->value && $start->isSameDay($end)) {
            return 0.5;
        }

        return $days;
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! $this->canUseColumns($table, $columns) || $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function canUseColumns(string $table, array $columns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function hasIndex(string $table, string $name): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => strcasecmp((string) ($index['name'] ?? ''), $name) === 0);
    }
};
