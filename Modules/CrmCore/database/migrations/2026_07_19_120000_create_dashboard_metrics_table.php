<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained('crm_sites')->nullOnDelete();
            $table->date('metric_date')->index();
            $table->unsignedInteger('reservations_today')->default(0);
            $table->decimal('monthly_revenue', 12, 2)->default(0);
            $table->unsignedInteger('pending_leaves')->default(0);
            $table->unsignedInteger('equipment_available')->default(0);
            $table->unsignedInteger('equipment_total')->default(0);
            $table->json('reservation_trend')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_metrics');
    }
};
