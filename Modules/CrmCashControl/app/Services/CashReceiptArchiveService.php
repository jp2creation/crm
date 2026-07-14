<?php

namespace Modules\CrmCashControl\Services;

use App\Models\CrmCashReceipt;
use App\Models\CrmCashReceiptArchive;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CashReceiptArchiveService
{
    public function cutoffDate(?int $years = null): CarbonImmutable
    {
        $years = max(1, $years ?: (int) config('crm.cash_control.archive_after_years', 3));

        return CarbonImmutable::now()->subYears($years)->startOfDay();
    }

    public function countArchiveCandidates(?CarbonInterface $cutoff = null): int
    {
        return CrmCashReceipt::query()
            ->archiveCandidates($cutoff ?: $this->cutoffDate())
            ->count();
    }

    public function archiveOlderThan(?CarbonInterface $cutoff = null, int $limit = 500): int
    {
        $cutoff ??= $this->cutoffDate();
        $limit = max(1, min($limit, 5000));

        $receipts = CrmCashReceipt::query()
            ->archive($cutoff)
            ->with('day:id,site_id,cash_date')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($receipts->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($receipts): int {
            $archived = 0;

            foreach ($receipts as $receipt) {
                CrmCashReceiptArchive::query()->updateOrCreate(
                    ['original_receipt_id' => $receipt->id],
                    [
                        'cash_register_day_id' => $receipt->cash_register_day_id,
                        'site_id' => $receipt->day?->site_id,
                        'cash_date' => $receipt->day?->cash_date?->toDateString(),
                        'invoice_number' => $receipt->invoice_number ?? '',
                        'customer_name' => $receipt->customer_name ?? '',
                        'occurred_on' => $receipt->occurred_on?->toDateString()
                            ?? $receipt->created_at?->toDateString()
                            ?? now()->toDateString(),
                        'invoice_total' => $receipt->invoice_total,
                        'cash_amount' => $receipt->cash_amount,
                        'card_amount' => $receipt->card_amount,
                        'check_amount' => $receipt->check_amount,
                        'transfer_amount' => $receipt->transfer_amount,
                        'control_amount' => $receipt->control_amount,
                        'payment_note' => $receipt->payment_note ?? '',
                        'sort_order' => $receipt->sort_order,
                        'created_by' => $receipt->created_by,
                        'updated_by' => $receipt->updated_by,
                        'archived_at' => now(),
                        'original_created_at' => $receipt->created_at,
                        'original_updated_at' => $receipt->updated_at,
                    ],
                );

                $receipt->delete();
                $archived++;
            }

            return $archived;
        });
    }
}
