<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const UNIMPLEMENTED_MODULES = [
        'planning',
        'documents',
        'demandes',
    ];

    public function up(): void
    {
        DB::table('crm_modules')
            ->whereIn('slug', self::UNIMPLEMENTED_MODULES)
            ->update([
                'active' => false,
                'updated_at' => now(),
            ]);

        DB::table('crm_menu_items')
            ->whereIn('item_key', array_map(
                static fn (string $slug): string => 'module:' . $slug,
                self::UNIMPLEMENTED_MODULES,
            ))
            ->update([
                'active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('crm_modules')
            ->whereIn('slug', self::UNIMPLEMENTED_MODULES)
            ->update([
                'active' => true,
                'updated_at' => now(),
            ]);

        DB::table('crm_menu_items')
            ->whereIn('item_key', array_map(
                static fn (string $slug): string => 'module:' . $slug,
                self::UNIMPLEMENTED_MODULES,
            ))
            ->update([
                'active' => true,
                'updated_at' => now(),
            ]);
    }
};
