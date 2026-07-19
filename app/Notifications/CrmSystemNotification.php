<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrmSystemNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $channels
     */
    public function __construct(
        public readonly string $templateKey,
        public readonly array $data = [],
        private readonly array $channels = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = $this->channels ?: config('crm.notifications.channels', ['database']);

        return collect($channels)
            ->map(fn (string $channel): string => trim($channel))
            ->filter()
            ->map(fn (string $channel): string => $channel === 'pwa' ? 'database' : $channel)
            ->unique()
            ->filter(fn (string $channel): bool => in_array($channel, ['mail', 'database'], true))
            ->when(
                blank($notifiable->email ?? null),
                fn ($collection) => $collection->reject(fn (string $channel): bool => $channel === 'mail'),
            )
            ->values()
            ->all();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payload = $this->payload();
        $message = (new MailMessage)
            ->subject($payload['subject'])
            ->greeting($payload['greeting'])
            ->line($payload['body']);

        if ($payload['actionUrl'] !== null) {
            $message->action($payload['actionLabel'], $payload['actionUrl']);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $payload = $this->payload();

        return [
            'templateKey' => $this->templateKey,
            'subject' => $payload['subject'],
            'body' => $payload['body'],
            'actionLabel' => $payload['actionLabel'],
            'actionUrl' => $payload['actionUrl'],
            'pwa' => [
                'title' => $payload['subject'],
                'body' => $payload['body'],
                'url' => $payload['actionUrl'] ?: '/',
                'icon' => '/assets/pwa/icon-192.png',
                'badge' => '/assets/pwa/maskable-192.png',
            ],
            'data' => $this->data,
        ];
    }

    /**
     * @return array{subject:string,greeting:string,body:string,actionLabel:string,actionUrl:?string}
     */
    private function payload(): array
    {
        $template = 'crm_notifications.'.$this->templateKey.'.';

        return [
            'subject' => __($template.'subject', $this->data),
            'greeting' => __($template.'greeting', $this->data) !== $template.'greeting'
                ? __($template.'greeting', $this->data)
                : __('crm_notifications.default.greeting'),
            'body' => __($template.'body', $this->data),
            'actionLabel' => __($template.'action', $this->data) !== $template.'action'
                ? __($template.'action', $this->data)
                : __('crm_notifications.default.action'),
            'actionUrl' => isset($this->data['actionUrl']) ? (string) $this->data['actionUrl'] : null,
        ];
    }
}
