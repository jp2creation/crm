<?php

namespace Modules\CrmCashControl\Console\Commands;

use Illuminate\Console\Command;
use Modules\CrmCashControl\Services\CashReceiptArchiveService;

class ArchiveCashReceiptsCommand extends Command
{
    protected $signature = 'cash-control:archive-receipts
        {--years= : Nombre d annees a conserver dans la table active}
        {--limit=500 : Taille de lot}
        {--dry-run : Compter sans archiver}';

    protected $description = 'Archive old cash-control invoice lines into crm_cash_receipt_archives.';

    public function handle(CashReceiptArchiveService $archives): int
    {
        $yearsOption = $this->option('years');
        $years = $yearsOption === null || $yearsOption === '' ? null : max(1, (int) $yearsOption);
        $limit = max(1, (int) $this->option('limit'));
        $cutoff = $archives->cutoffDate($years);
        $candidates = $archives->countArchiveCandidates($cutoff);

        if ($this->option('dry-run')) {
            $this->info("Factures candidates avant {$cutoff->toDateString()} : {$candidates}");

            return self::SUCCESS;
        }

        $archived = 0;

        do {
            $batch = $archives->archiveOlderThan($cutoff, $limit);
            $archived += $batch;
        } while ($batch === $limit);

        $this->info("Factures archivees : {$archived}");

        return self::SUCCESS;
    }
}
