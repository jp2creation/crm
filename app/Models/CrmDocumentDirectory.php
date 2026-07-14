<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmDocumentDirectory extends Model
{
    protected $table = 'crm_document_directories';

    protected $fillable = [
        'site_id',
        'category',
        'parent_id',
        'name',
        'description',
        'visibility',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CrmDocument::class, 'directory_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(CrmUser::class, 'updated_by');
    }

    public function scopeForContext(Builder $query, int $siteId, string $category): Builder
    {
        return $query
            ->where('site_id', $siteId)
            ->where('category', $category);
    }

    public function isEmpty(): bool
    {
        return ! $this->children()->exists() && ! $this->documents()->exists();
    }
}
