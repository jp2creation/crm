<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_menu_items')) {
            return;
        }

        $templateKeys = [
            'app:contacts',
            'app:blog',
            'app:ecommerce',
            'app:kanban',
            'app:email',
            'app:chat',
            'app:calendar',
            'app:notes',
            'auth:login',
            'auth:register',
            'auth:forgot-password',
            'page:pricing',
            'page:account-settings',
            'page:gallery',
            'page:faq',
            'page:typography',
            'chart:line',
            'chart:area',
            'chart:columns',
            'chart:pie',
            'chart:radar',
            'chart:candlestick',
            'dashboard:overview',
            'dashboard:analytics',
            'dashboard:ecommerce',
            'dashboard:crm',
            'form:layout',
            'form:validation',
            'form:editor',
            'table:simple',
            'table:data',
            'table:crud',
            'feature:rule-engine',
            'feature:query-builder',
            'feature:simulation',
            'feature:insights',
            'feature:workflow-builder',
            'feature:task-scheduler',
        ];

        DB::table('crm_menu_items')
            ->whereIn('item_key', $templateKeys)
            ->update([
                'active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_menu_items')) {
            return;
        }

        DB::table('crm_menu_items')
            ->whereIn('item_key', [
                'page:pricing',
                'page:account-settings',
                'page:gallery',
                'page:faq',
                'page:typography',
            ])
            ->update([
                'active' => true,
                'updated_at' => now(),
            ]);
    }
};
