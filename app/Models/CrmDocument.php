<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CrmDocument extends Model
{
    protected $table = 'crm_documents';

    protected $fillable = [
        'site_id',
        'category',
        'directory_id',
        'name',
        'description',
        'visibility',
        'disk',
        'disk_path',
        'original_name',
        'mime_type',
        'size',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (CrmDocument $document): void {
            if ($document->disk && $document->disk_path) {
                Storage::disk($document->disk)->delete($document->disk_path);
            }
        });
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(CrmSite::class, 'site_id');
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(CrmDocumentDirectory::class, 'directory_id');
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

    public function readableSize(): string
    {
        $bytes = max(0, (int) $this->size);

        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $size = $bytes / (1024 ** $power);

        return str_replace('.', ',', (string) round($size, 1)).' '.$units[$power];
    }

    public function downloadName(): string
    {
        return $this->original_name ?: $this->name;
    }
}
