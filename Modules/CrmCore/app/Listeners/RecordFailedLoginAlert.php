<?php

namespace Modules\CrmCore\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Modules\CrmCore\Services\CrmMonitoringAlertService;

class RecordFailedLoginAlert
{
    public function __construct(
        private readonly CrmMonitoringAlertService $alerts,
        private readonly Request $request,
    ) {}

    public function handle(Failed $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        $email = (string) ($event->credentials['email'] ?? '');

        if ($email === '') {
            return;
        }

        $this->alerts->recordFailedLogin($this->request, $email);
    }
}
