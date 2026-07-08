<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CrmEquipmentCategory extends Model
{
    protected $table = 'crm_equipment_categories';

    protected $fillable = [
        'name',
        'slug',
        'active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CrmEquipmentCategory $category): void {
            if (blank($category->slug) || $category->isDirty('name')) {
                $category->slug = static::uniqueSlug($category->name, $category->getKey());
            }
        });

        static::deleting(function (CrmEquipmentCategory $category): void {
            if ($category->equipmentItems()->exists()) {
                throw ValidationException::withMessages([
                    'category' => 'Cette categorie contient du materiel. Desactive-la pour la masquer.',
                ]);
            }
        });
    }

    public static function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'categorie';
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

    public function equipmentItems(): HasMany
    {
        return $this->hasMany(CrmEquipmentItem::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
