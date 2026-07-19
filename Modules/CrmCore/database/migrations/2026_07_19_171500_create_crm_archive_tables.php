<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->archiveTables() as $tableName) {
            $this->createArchiveTable($tableName);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->archiveTables()) as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }

    /**
     * @return array<int, string>
     */
    private function archiveTables(): array
    {
        return [
            'crm_archived_reservations',
            'crm_archived_equipment_rentals',
            'crm_archived_leave_entries',
            'crm_archived_cash_register_days',
            'crm_archived_check_remittances',
            'crm_archived_deposit_requests',
            'crm_archived_sales_tours',
        ];
    }

    private function createArchiveTable(string $tableName): void
    {
        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('original_model', 190)->nullable();
            $table->string('original_table', 120)->nullable();
            $table->unsignedBigInteger('original_id');
            $table->json('data');
            $table->timestamp('archived_at')->useCurrent();
            $table->string('archived_by', 120)->nullable();
            $table->dateTime('original_started_at')->nullable();
            $table->dateTime('original_ended_at')->nullable();
            $table->timestamps();

            $table->unique('original_id');
            $table->index('original_model');
            $table->index('archived_at');
            $table->index('original_ended_at');
        });
    }
};
