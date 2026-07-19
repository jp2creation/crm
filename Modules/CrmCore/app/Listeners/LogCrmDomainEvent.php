<?php

namespace Modules\CrmCore\Listeners;

use Illuminate\Support\Facades\DB;
use Modules\CrmCore\Events\CrmDomainEvent;

class LogCrmDomainEvent
{
    public function handle(CrmDomainEvent $event): void
    {
        DB::table('crm_logs')->insert([
            'user_id' => $event->actorId ?? 0,
            'user_name' => $event->actorName,
            'action' => $this->actionLabel($event),
            'details' => $this->details($event),
            'created_at' => $event->occurredAt(),
            'ip' => request()->ip() ?? '',
        ]);
    }

    private function actionLabel(CrmDomainEvent $event): string
    {
        if ($event->module === 'reservations' && $event->entity === 'reservation') {
            return match ($event->name) {
                'created' => 'creation reservation',
                'updated' => 'modification reservation',
                'deleted' => 'suppression reservation',
                default => $event->key(),
            };
        }

        return $event->key();
    }

    private function details(CrmDomainEvent $event): string
    {
        $label = $event->entityId ? ucfirst($event->entity).' #'.$event->entityId : ucfirst($event->entity);
        $title = trim((string) ($event->payload['title'] ?? ''));

        return $title !== '' ? $label.' - '.$title : $label;
    }
}
