<?php

namespace Modules\CrmCore\Services;

use App\Models\CrmNotificationLog;
use App\Models\User;
use App\Notifications\CrmSystemNotification;
use Throwable;

class CrmNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>|null  $channels
     */
    public function notify(User $user, string $templateKey, array $data = [], ?array $channels = null): void
    {
        $channels = $channels ?: config('crm.notifications.channels', ['database']);
        $notification = new CrmSystemNotification($templateKey, $data, $channels);
        $logs = collect();

        foreach ($this->logChannels($channels) as $channel) {
            $logs->push($this->createLog($user, $templateKey, $data, $channel));
        }

        try {
            $user->notify($notification);
            $logs->each->markSent();
        } catch (Throwable $error) {
            $logs->each(fn (CrmNotificationLog $log) => $log->markFailed($error->getMessage()));

            report($error);
        }
    }

    /**
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    private function logChannels(array $channels): array
    {
        return collect($channels)
            ->map(fn (string $channel): string => trim($channel))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createLog(User $user, string $templateKey, array $data, string $channel): CrmNotificationLog
    {
        return CrmNotificationLog::query()->create([
            'channel' => $channel,
            'notifiable_type' => $user::class,
            'notifiable_id' => $user->id,
            'recipient' => $channel === 'mail' ? $user->email : (string) $user->id,
            'subject' => __('crm_notifications.'.$templateKey.'.subject', $data),
            'template_key' => $templateKey,
            'locale' => (string) config('crm.notifications.locale', app()->getLocale()),
            'status' => CrmNotificationLog::STATUS_PENDING,
            'payload' => $data,
        ]);
    }
}
