<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_cash_receipts')) {
            return;
        }

        Schema::create('crm_cash_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cash_register_day_id')->constrained('crm_cash_register_days')->cascadeOnDelete();
            $table->string('invoice_number', 80)->default('');
            $table->string('customer_name', 190)->default('');
            $table->date('occurred_on');
            $table->decimal('invoice_total', 12, 2)->default(0);
            $table->decimal('cash_amount', 12, 2)->default(0);
            $table->decimal('card_amount', 12, 2)->default(0);
            $table->decimal('check_amount', 12, 2)->default(0);
            $table->decimal('transfer_amount', 12, 2)->default(0);
            $table->decimal('control_amount', 12, 2)->default(0);
            $table->string('payment_note', 190)->default('');
            $table->integer('sort_order')->default(100);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();

            $table->index(['cash_register_day_id', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_cash_receipts');
    }
};
