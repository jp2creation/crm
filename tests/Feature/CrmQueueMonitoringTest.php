<?php

namespace Tests\Feature;

use App\Models\CrmNotificationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmQueueMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_monitor_records_alert_when_waiting_jobs_exceed_threshold(): void
    {
        config(['queue.default' => 'database']);

        DB::table('jobs')->insert([
            [
                'queue' => 'default',
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ],
            [
                'queue' => 'default',
                'payload' => '{}',
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ],
        ]);

        $this->artisan('crm:monitor-queue-size', ['--threshold' => 1])
            ->assertSuccessful();

        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'monitoring',
            'recipient' => 'queue:default',
            'template_key' => 'queue.size.threshold',
            'status' => CrmNotificationLog::STATUS_SENT,
        ]);

        $alert = CrmNotificationLog::query()
            ->where('template_key', 'queue.size.threshold')
            ->firstOrFail();

        $this->assertSame('default', $alert->payload['queue']);
        $this->assertSame(2, $alert->payload['jobs']);
        $this->assertSame(1, $alert->payload['threshold']);
    }
}
