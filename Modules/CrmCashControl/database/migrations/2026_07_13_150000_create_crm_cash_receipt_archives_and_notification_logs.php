<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_cash_receipt_archives')) {
            Schema::create('crm_cash_receipt_archives', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('original_receipt_id')->unique();
                $table->unsignedBigInteger('cash_register_day_id')->nullable()->index();
                $table->foreignId('site_id')->nullable()->constrained('crm_sites')->nullOnDelete();
                $table->date('cash_date')->nullable()->index();
                $table->string('invoice_number', 80)->default('');
                $table->string('customer_name', 190)->default('');
                $table->date('occurred_on')->index();
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
                $table->timestamp('archived_at')->useCurrent();
                $table->timestamp('original_created_at')->nullable();
                $table->timestamp('original_updated_at')->nullable();
                $table->timestamps();

                $table->index(['site_id', 'cash_date']);
                $table->index(['occurred_on', 'invoice_number']);
            });
        }

        if (! Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('channel', 50)->default('mail');
                $table->nullableMorphs('notifiable');
                $table->string('recipient', 190)->nullable()->index();
                $table->string('subject', 190)->nullable();
                $table->string('template_key', 120)->nullable()->index();
                $table->string('locale', 10)->default('fr');
                $table->string('status', 30)->default('pending')->index();
                $table->text('error_message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamps();

                $table->index(['channel', 'status']);
                $table->index(['template_key', 'locale']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('crm_cash_receipt_archives');
    }
};
