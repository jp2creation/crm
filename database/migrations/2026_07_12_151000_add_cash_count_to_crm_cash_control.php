<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_cash_register_days', function (Blueprint $table): void {
            if (! Schema::hasColumn('crm_cash_register_days', 'check_counted')) {
                $table->decimal('check_counted', 12, 2)->nullable()->after('bank_counted');
            }

            if (! Schema::hasColumn('crm_cash_register_days', 'transfer_counted')) {
                $table->decimal('transfer_counted', 12, 2)->nullable()->after('check_counted');
            }

            if (! Schema::hasColumn('crm_cash_register_days', 'card_counted')) {
                $table->decimal('card_counted', 12, 2)->nullable()->after('transfer_counted');
            }
        });

        if (Schema::hasTable('crm_cash_count_lines')) {
            return;
        }

        Schema::create('crm_cash_count_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_register_day_id')->constrained('crm_cash_register_days')->cascadeOnDelete();
            $table->string('kind', 20)->default('bill');
            $table->decimal('denomination', 8, 2);
            $table->unsignedInteger('previous_quantity')->default(0);
            $table->unsignedInteger('current_quantity')->default(0);
            $table->unsignedInteger('deposit_quantity')->default(0);
            $table->integer('sort_order')->default(100);
            $table->timestamps();

            $table->unique(['cash_register_day_id', 'denomination']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_cash_count_lines');

        Schema::table('crm_cash_register_days', function (Blueprint $table): void {
            if (Schema::hasColumn('crm_cash_register_days', 'card_counted')) {
                $table->dropColumn('card_counted');
            }

            if (Schema::hasColumn('crm_cash_register_days', 'transfer_counted')) {
                $table->dropColumn('transfer_counted');
            }

            if (Schema::hasColumn('crm_cash_register_days', 'check_counted')) {
                $table->dropColumn('check_counted');
            }
        });
    }
};
