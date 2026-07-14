<?php

namespace Tests\Feature;

use App\Models\CrmCashReceipt;
use App\Models\CrmCashRegisterDay;
use App\Models\CrmNotificationLog;
use App\Models\CrmSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmCashMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_cash_receipts_are_moved_to_archive_table(): void
    {
        $site = $this->createSite();
        $oldDay = $this->createDay($site, '2020-01-10');
        $recentDay = $this->createDay($site, '2099-01-10');

        $oldReceipt = CrmCashReceipt::query()->create([
            'cash_register_day_id' => $oldDay->id,
            'invoice_number' => 'OLD-001',
            'customer_name' => 'Client archive',
            'occurred_on' => '2020-01-10',
            'invoice_total' => 120,
            'cash_amount' => 120,
        ]);

        $recentReceipt = CrmCashReceipt::query()->create([
            'cash_register_day_id' => $recentDay->id,
            'invoice_number' => 'RECENT-001',
            'customer_name' => 'Client actif',
            'occurred_on' => '2099-01-10',
            'invoice_total' => 240,
            'card_amount' => 240,
        ]);

        $this->assertSame(1, CrmCashReceipt::query()->archiveCandidates(now()->subYears(3))->count());

        $this->artisan('cash-control:archive-receipts', ['--years' => 3])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('crm_cash_receipts', ['id' => $oldReceipt->id]);
        $this->assertDatabaseHas('crm_cash_receipt_archives', [
            'original_receipt_id' => $oldReceipt->id,
            'site_id' => $site->id,
            'cash_register_day_id' => $oldDay->id,
            'invoice_number' => 'OLD-001',
            'customer_name' => 'Client archive',
        ]);
        $this->assertDatabaseHas('crm_cash_receipts', ['id' => $recentReceipt->id]);
    }

    public function test_notification_log_can_trace_delivery_failure(): void
    {
        $log = CrmNotificationLog::query()->create([
            'channel' => 'mail',
            'recipient' => 'client@example.test',
            'subject' => __('crm_notifications.invoice_reminder.subject', ['invoice' => 'F-001'], 'fr'),
            'template_key' => 'invoice_reminder',
            'locale' => config('crm.notifications.locale'),
            'status' => CrmNotificationLog::STATUS_PENDING,
            'payload' => ['invoice' => 'F-001', 'days' => 15],
        ]);

        $log->markFailed('SMTP bounce');

        $this->assertSame(1, CrmNotificationLog::query()->failed()->count());
        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => CrmNotificationLog::STATUS_FAILED,
            'error_message' => 'SMTP bounce',
        ]);
    }

    private function createSite(): CrmSite
    {
        return CrmSite::query()->create([
            'name' => 'Palissy Test',
            'slug' => 'palissy-test',
            'active' => true,
            'morning_start' => '07:30:00',
            'morning_end' => '12:00:00',
            'afternoon_start' => '13:30:00',
            'afternoon_end' => '17:30:00',
        ]);
    }

    private function createDay(CrmSite $site, string $date): CrmCashRegisterDay
    {
        return CrmCashRegisterDay::query()->create([
            'site_id' => $site->id,
            'cash_date' => $date,
            'opening_balance' => 0,
            'invoice_total' => 0,
            'cash_sales' => 0,
            'card_sales' => 0,
            'check_sales' => 0,
            'transfer_sales' => 0,
            'invoice_errors_count' => 0,
            'status' => CrmCashRegisterDay::STATUS_OK,
        ]);
    }
}
