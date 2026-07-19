<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_deposit_requests') || Schema::hasColumn('crm_deposit_requests', 'client_name')) {
            return;
        }

        Schema::table('crm_deposit_requests', function (Blueprint $table): void {
            $table->string('client_name', 190)->nullable()->index()->after('document_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_deposit_requests') || ! Schema::hasColumn('crm_deposit_requests', 'client_name')) {
            return;
        }

        Schema::table('crm_deposit_requests', function (Blueprint $table): void {
            $table->dropColumn('client_name');
        });
    }
};
