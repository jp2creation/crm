<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_feature_flags')) {
            Schema::create('crm_feature_flags', function (Blueprint $table): void {
                $table->id();
                $table->string('flag_key')->unique();
                $table->string('scope')->default('global')->index();
                $table->string('name')->nullable();
                $table->text('description')->nullable();
                $table->boolean('enabled')->default(true)->index();
                $table->json('payload')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();
            });
        }

        $moduleRows = Schema::hasTable('crm_modules')
            ? DB::table('crm_modules')->orderBy('sort_order')->get(['slug', 'name', 'description'])
            : collect();

        $now = now();

        foreach ($moduleRows as $module) {
            DB::table('crm_feature_flags')->updateOrInsert(
                ['flag_key' => 'module:'.$module->slug],
                [
                    'scope' => 'module',
                    'name' => $module->name,
                    'description' => $module->description,
                    'enabled' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_feature_flags');
    }
};
