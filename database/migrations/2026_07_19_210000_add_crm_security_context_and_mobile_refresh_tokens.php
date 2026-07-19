<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_logs')) {
            Schema::table('crm_logs', function (Blueprint $table): void {
                if (! Schema::hasColumn('crm_logs', 'user_agent')) {
                    $table->string('user_agent', 512)->nullable()->after('ip');
                }

                if (! Schema::hasColumn('crm_logs', 'context')) {
                    $table->json('context')->nullable()->after('user_agent');
                }
            });
        }

        if (! Schema::hasTable('crm_mobile_refresh_tokens')) {
            Schema::create('crm_mobile_refresh_tokens', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('personal_access_token_id')->nullable()->constrained('personal_access_tokens')->nullOnDelete();
                $table->string('token_hash', 64)->unique();
                $table->string('device_name', 120);
                $table->json('abilities');
                $table->timestamp('expires_at')->index();
                $table->timestamp('revoked_at')->nullable()->index();
                $table->timestamp('last_used_at')->nullable();
                $table->string('ip_address', 80)->nullable();
                $table->string('user_agent', 512)->nullable();
                $table->timestamps();

                $table->index(['user_id', 'revoked_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_mobile_refresh_tokens');

        if (Schema::hasTable('crm_logs')) {
            Schema::table('crm_logs', function (Blueprint $table): void {
                if (Schema::hasColumn('crm_logs', 'context')) {
                    $table->dropColumn('context');
                }

                if (Schema::hasColumn('crm_logs', 'user_agent')) {
                    $table->dropColumn('user_agent');
                }
            });
        }
    }
};
