<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_sites')) {
            Schema::create('crm_sites', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120)->unique();
                $table->string('slug', 140)->unique();
                $table->boolean('active')->default(true);
                $table->time('morning_start')->default('07:30:00');
                $table->time('morning_end')->default('12:00:00');
                $table->time('afternoon_start')->default('13:30:00');
                $table->time('afternoon_end')->default('17:30:00');
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('crm_sites', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_sites', 'morning_start')) {
                    $table->time('morning_start')->default('07:30:00')->after('active');
                }
                if (! Schema::hasColumn('crm_sites', 'morning_end')) {
                    $table->time('morning_end')->default('12:00:00')->after('morning_start');
                }
                if (! Schema::hasColumn('crm_sites', 'afternoon_start')) {
                    $table->time('afternoon_start')->default('13:30:00')->after('morning_end');
                }
                if (! Schema::hasColumn('crm_sites', 'afternoon_end')) {
                    $table->time('afternoon_end')->default('17:30:00')->after('afternoon_start');
                }
                if (! Schema::hasColumn('crm_sites', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }

        if (! Schema::hasTable('crm_modules')) {
            Schema::create('crm_modules', function (Blueprint $table) {
                $table->id();
                $table->string('name', 120);
                $table->string('slug', 140)->unique();
                $table->string('description')->default('');
                $table->string('route_path', 160)->default('');
                $table->string('menu_badge', 40)->nullable();
                $table->boolean('show_menu_badge')->default(false);
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamps();
            });
        } else {
            Schema::table('crm_modules', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_modules', 'menu_badge')) {
                    $table->string('menu_badge', 40)->nullable()->after('route_path');
                }

                if (! Schema::hasColumn('crm_modules', 'show_menu_badge')) {
                    $table->boolean('show_menu_badge')->default(false)->after('menu_badge');
                }
            });
        }

        if (! Schema::hasTable('crm_permissions')) {
            Schema::create('crm_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160)->unique();
                $table->string('label', 190);
                $table->string('group_label', 80);
                $table->integer('sort_order')->default(100);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crm_menu_groups')) {
            Schema::create('crm_menu_groups', function (Blueprint $table) {
                $table->id();
                $table->string('menu_key', 80)->unique();
                $table->string('title', 120);
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('crm_menu_items')) {
            Schema::create('crm_menu_items', function (Blueprint $table) {
                $table->id();
                $table->string('item_key', 120)->unique();
                $table->string('group_key', 80);
                $table->string('icon_key', 80)->default('');
                $table->string('label', 160);
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamps();

                $table->index(['group_key', 'sort_order']);
            });
        } else {
            Schema::table('crm_menu_items', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_menu_items', 'icon_key')) {
                    $table->string('icon_key', 80)->default('')->after('group_key');
                }
            });
        }

        if (! Schema::hasTable('crm_users')) {
            Schema::create('crm_users', function (Blueprint $table) {
                $table->id();
                $table->string('name', 160)->unique();
                $table->string('first_name', 80)->nullable();
                $table->string('last_name', 80)->nullable();
                $table->string('email', 190)->nullable();
                $table->string('bio', 255)->nullable();
                $table->string('photo_url', 255)->nullable();
                $table->string('role', 40)->default('user');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('crm_users', function (Blueprint $table) {
                if (! Schema::hasColumn('crm_users', 'first_name')) {
                    $table->string('first_name', 80)->nullable()->after('name');
                }
                if (! Schema::hasColumn('crm_users', 'last_name')) {
                    $table->string('last_name', 80)->nullable()->after('first_name');
                }
                if (! Schema::hasColumn('crm_users', 'email')) {
                    $table->string('email', 190)->nullable()->after('last_name');
                }
                if (! Schema::hasColumn('crm_users', 'bio')) {
                    $table->string('bio', 255)->nullable()->after('email');
                }
                if (! Schema::hasColumn('crm_users', 'photo_url')) {
                    $table->string('photo_url', 255)->nullable()->after('bio');
                }
            });
        }

        if (! Schema::hasTable('crm_user_sites')) {
            Schema::create('crm_user_sites', function (Blueprint $table) {
                $table->foreignId('site_id');
                $table->foreignId('user_id');
                $table->boolean('is_default')->default(false);
                $table->timestamp('created_at')->nullable();

                $table->primary(['site_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('crm_user_modules')) {
            Schema::create('crm_user_modules', function (Blueprint $table) {
                $table->foreignId('module_id');
                $table->foreignId('user_id');
                $table->timestamp('created_at')->nullable();

                $table->primary(['module_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('crm_user_permissions')) {
            Schema::create('crm_user_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id');
                $table->foreignId('user_id');
                $table->timestamp('created_at')->nullable();

                $table->primary(['permission_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('crm_vehicles')) {
            Schema::create('crm_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id');
                $table->string('name', 160)->unique();
                $table->string('description')->default('');
                $table->string('color', 20)->default('#95002e');
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index(['site_id', 'active']);
            });
        }

        if (! Schema::hasTable('crm_reservations')) {
            Schema::create('crm_reservations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('site_id');
                $table->foreignId('vehicle_id');
                $table->foreignId('user_id');
                $table->string('user_name', 160);
                $table->string('title', 190)->default('');
                $table->string('contact_phone', 40)->default('');
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['vehicle_id', 'start_at', 'end_at']);
                $table->index(['site_id', 'start_at']);
            });
        }

        if (! Schema::hasTable('crm_logs')) {
            Schema::create('crm_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->string('user_name', 160)->nullable();
                $table->string('action', 160);
                $table->text('details')->nullable();
                $table->dateTime('created_at');
                $table->string('ip', 80)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'crm_logs',
            'crm_reservations',
            'crm_vehicles',
            'crm_user_permissions',
            'crm_user_modules',
            'crm_user_sites',
            'crm_users',
            'crm_menu_items',
            'crm_menu_groups',
            'crm_permissions',
            'crm_modules',
            'crm_sites',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
