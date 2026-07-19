<?php

namespace Modules\CrmCore\Console\Commands;

use App\Models\CrmNotificationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitorQueueSizeCommand extends Command
{
    protected $signature = 'crm:monitor-queue-size
        {--threshold= : Nombre de jobs en attente avant alerte}';

    protected $description = 'Monitor CRM queue size and register an alert when it exceeds the configured threshold.';

    public function handle(): int
    {
        $threshold = max(1, (int) ($this->option('threshold') ?: config('crm.queue.alert_threshold', 1000)));
        $connection = (string) config('queue.default', 'sync');
        $queueConfig = config("queue.connections.{$connection}", []);
        $table = (string) ($queueConfig['table'] ?? config('queue.connections.database.table', 'jobs'));

        if (! Schema::hasTable($table)) {
            $this->info('Table de queue absente : '.$table);

            return self::SUCCESS;
        }

        $rows = DB::table($table)
            ->select('queue', DB::raw('COUNT(*) as total'))
            ->whereNull('reserved_at')
            ->groupBy('queue')
            ->get();

        $alerts = 0;

        foreach ($rows as $row) {
            $queue = (string) ($row->queue ?: 'default');
            $total = (int) $row->total;

            if ($total <= $threshold) {
                continue;
            }

            $alerts++;
            $this->warn("Queue {$queue} : {$total} jobs en attente (seuil {$threshold}).");
            $this->recordAlert($queue, $total, $threshold);
        }

        if ($alerts === 0) {
            $this->info('Queue CRM OK.');
        }

        return self::SUCCESS;
    }

    private function recordAlert(string $queue, int $total, int $threshold): void
    {
        $cooldown = max(1, (int) config('crm.queue.alert_cooldown_minutes', 30));
        $recipient = 'queue:'.$queue;

        $alreadyReported = CrmNotificationLog::query()
            ->where('channel', 'monitoring')
            ->where('template_key', 'queue.size.threshold')
            ->where('recipient', $recipient)
            ->where('created_at', '>=', now()->subMinutes($cooldown))
            ->exists();

        if ($alreadyReported) {
            return;
        }

        CrmNotificationLog::query()->create([
            'channel' => 'monitoring',
            'recipient' => $recipient,
            'subject' => 'Alerte queue CRM',
            'template_key' => 'queue.size.threshold',
            'locale' => (string) config('crm.notifications.locale', 'fr'),
            'status' => CrmNotificationLog::STATUS_SENT,
            'payload' => [
                'queue' => $queue,
                'jobs' => $total,
                'threshold' => $threshold,
            ],
            'sent_at' => now(),
        ]);
    }
}
