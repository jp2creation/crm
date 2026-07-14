<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\CrmCore\Support\CrmReferenceCache;

class CrmModule extends Model
{
    protected $table = 'crm_modules';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'route_path',
        'menu_badge',
        'show_menu_badge',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'show_menu_badge' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CrmModule $module): void {
            if (blank($module->slug)) {
                $module->slug = static::uniqueSlug($module->name, $module->getKey());
            }

            if (blank($module->route_path)) {
                $module->route_path = '/'.$module->slug;
            }
        });

        static::saved(function (CrmModule $module): void {
            $oldSlug = $module->getOriginal('slug') ?: $module->slug;

            $menuItem = CrmMenuItem::query()
                ->where('item_key', 'module:'.$oldSlug)
                ->first();

            if (! $menuItem) {
                $menuItem = CrmMenuItem::firstOrNew(['item_key' => 'module:'.$module->slug]);
            }

            $menuItemExists = $menuItem->exists;

            $menuItem->fill([
                'item_key' => 'module:'.$module->slug,
                'group_key' => $menuItemExists ? $menuItem->group_key : static::defaultMenuGroup($module->slug),
                'icon_key' => $menuItem->icon_key ?: static::defaultIconKey($module->slug),
                'label' => $menuItemExists ? ($menuItem->label ?: $module->name) : $module->name,
                'active' => $menuItemExists ? ((bool) $menuItem->active && (bool) $module->active) : (bool) $module->active,
                'sort_order' => $menuItemExists ? $menuItem->sort_order : $module->sort_order,
            ]);

            $menuItem->saveQuietly();
            CrmReferenceCache::forgetModules();
        });

        static::deleting(function (CrmModule $module): void {
            if (in_array($module->slug, ['reservations', 'administration'], true)) {
                throw ValidationException::withMessages([
                    'module' => 'Ce module est central. Desactive-le pour le masquer.',
                ]);
            }

            $module->users()->detach();
            $module->siteModulePermissions()->delete();
            CrmMenuItem::query()->where('item_key', 'module:'.$module->slug)->delete();
        });

        static::deleted(function (): void {
            CrmReferenceCache::forgetModules();
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'module';
        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public static function defaultIconKey(string $slug): string
    {
        return [
            'reservations' => 'truck',
            'administration' => 'settings',
            'planning' => 'calendar',
            'documents' => 'article',
            'demandes' => 'checklist',
            'tapis-romus' => 'table',
            'locations-materiel' => 'package',
            'pages-crm' => 'article',
            'conges' => 'calendar',
            'controle-caisse' => 'creditCard',
            'remise-cheques' => 'creditCard',
            'addvance' => 'creditCard',
        ][$slug] ?? 'category';
    }

    public static function defaultMenuGroup(string $slug): string
    {
        if (in_array($slug, ['controle-caisse', 'remise-cheques', 'addvance'], true)) {
            return 'accounting';
        }

        return in_array($slug, ['reservations', 'locations-materiel', 'tapis-romus'], true)
            ? 'apps'
            : 'internal';
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(CrmUser::class, 'crm_user_modules', 'module_id', 'user_id');
    }

    public function siteModulePermissions(): HasMany
    {
        return $this->hasMany(CrmUserSiteModulePermission::class, 'module_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
