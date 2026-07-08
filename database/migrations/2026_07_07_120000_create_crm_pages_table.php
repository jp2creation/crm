<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_pages')) {
            Schema::create('crm_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title', 160);
                $table->string('slug', 180)->unique();
                $table->string('excerpt', 255)->default('');
                $table->mediumText('content')->nullable();
                $table->string('icon_key', 80)->default('article');
                $table->boolean('active')->default(true);
                $table->boolean('show_in_menu')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamps();
            });
        }

        $now = now();

        DB::table('crm_permissions')->updateOrInsert(
            ['name' => 'pages.view'],
            [
                'label' => 'Voir les pages CRM',
                'group_label' => 'Pages CRM',
                'sort_order' => 190,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('crm_permissions')->updateOrInsert(
            ['name' => 'pages.manage'],
            [
                'label' => 'Gerer les pages CRM',
                'group_label' => 'Pages CRM',
                'sort_order' => 200,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('crm_modules')->updateOrInsert(
            ['slug' => 'pages-crm'],
            [
                'name' => 'Pages CRM',
                'description' => 'Pages internes modifiables depuis le CRM',
                'route_path' => '/pages-crm',
                'active' => true,
                'sort_order' => 18,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('crm_menu_items')->updateOrInsert(
            ['item_key' => 'module:pages-crm'],
            [
                'group_key' => 'internal',
                'icon_key' => 'article',
                'label' => 'Pages CRM',
                'active' => true,
                'sort_order' => 18,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        DB::table('crm_menu_items')->where('item_key', 'module:pages-crm')->orWhere('item_key', 'like', 'cms-page:%')->delete();
        DB::table('crm_modules')->where('slug', 'pages-crm')->delete();
        DB::table('crm_permissions')->whereIn('name', ['pages.view', 'pages.manage'])->delete();
        Schema::dropIfExists('crm_pages');
    }
};
