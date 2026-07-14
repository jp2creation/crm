<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmNotificationLog extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $table = 'notification_logs';

    protected $fillable = [
        'channel',
        'notifiable_type',
        'notifiable_id',
        'recipient',
        'subject',
        'template_key',
        'locale',
        'status',
        'error_message',
        'payload',
        'sent_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function markSent(): self
    {
        $this->forceFill([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ])->save();

        return $this;
    }

    public function markFailed(string $message): self
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error_message' => $message,
        ])->save();

        return $this;
    }
}
