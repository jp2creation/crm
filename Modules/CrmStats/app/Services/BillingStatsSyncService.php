<?php

namespace Modules\CrmStats\Services;

use App\Models\CachedBillingStat;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingStatsSyncService
{
    public function __construct(
        private BillingApiService $api,
    ) {}

    /**
     * @return array{synced:int, invoices:int, clients:int, products:int, warning:?string}
     */
    public function sync(?CarbonInterface $fromDate = null, ?int $siteId = null): array
    {
        return Cache::lock('crm-stats:sync-billing-data', 600)->block(5, function () use ($fromDate, $siteId): array {
            $filters = $fromDate ? ['date_from' => $fromDate->toDateString(), 'updated_at' => $fromDate->toDateString()] : [];

            $clients = collect($this->api->getClients($filters))->keyBy(
                fn (array $client): string => (string) ($client['id'] ?? $client['external_id'] ?? $client['reference'] ?? ''),
            );
            $products = collect($this->api->getProducts())->keyBy(
                fn (array $product): string => (string) ($product['id'] ?? $product['external_id'] ?? $product['reference'] ?? ''),
            );
            $invoices = $this->api->getInvoices($filters);

            $aggregates = [];

            foreach ($invoices as $invoice) {
                foreach ($this->invoiceLines($invoice) as $line) {
                    $date = $this->invoiceDate($invoice);
                    $clientId = $this->clientId($invoice);
                    $productFamily = $this->productFamily($line, $products->all());
                    $aggregateSiteId = $siteId ?? $this->siteId($invoice);
                    $key = implode('|', [
                        $aggregateSiteId ?: 'global',
                        $date,
                        $clientId,
                        $productFamily,
                    ]);

                    $aggregates[$key] ??= [
                        'site_id' => $aggregateSiteId,
                        'date' => $date,
                        'client_id' => $clientId,
                        'client_name' => $this->clientName($invoice, $clients->get($clientId)),
                        'client_status' => $this->clientStatus($invoice, $clients->get($clientId), $fromDate),
                        'product_family' => $productFamily,
                        'total_amount' => 0.0,
                        'invoice_count' => 0,
                        'quantity' => 0.0,
                        'last_invoice_date' => $date,
                        'raw_payload' => [
                            'invoice_ids' => [],
                        ],
                    ];

                    $aggregates[$key]['total_amount'] += $this->lineAmount($line, $invoice);
                    $aggregates[$key]['quantity'] += $this->lineQuantity($line);
                    $aggregates[$key]['raw_payload']['invoice_ids'][] = (string) ($invoice['id'] ?? $invoice['external_id'] ?? $invoice['number'] ?? Str::uuid());
                }
            }

            DB::transaction(function () use (&$aggregates): void {
                foreach ($aggregates as $aggregate) {
                    $aggregate['invoice_count'] = count(array_unique($aggregate['raw_payload']['invoice_ids']));
                    $aggregate['total_amount'] = round((float) $aggregate['total_amount'], 2);
                    $aggregate['quantity'] = round((float) $aggregate['quantity'], 3);
                    $aggregate['raw_payload']['invoice_ids'] = array_values(array_unique($aggregate['raw_payload']['invoice_ids']));

                    CachedBillingStat::query()->updateOrCreate(
                        [
                            'site_id' => $aggregate['site_id'],
                            'date' => $aggregate['date'],
                            'client_id' => $aggregate['client_id'],
                            'product_family' => $aggregate['product_family'],
                        ],
                        Arr::except($aggregate, ['site_id', 'date', 'client_id', 'product_family']),
                    );
                }
            });

            app(BillingStatsDashboardService::class)->clearCache();

            return [
                'synced' => count($aggregates),
                'invoices' => count($invoices),
                'clients' => $clients->count(),
                'products' => $products->count(),
                'warning' => $this->api->lastError(),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function invoiceLines(array $invoice): array
    {
        $lines = $invoice['lines'] ?? $invoice['items'] ?? [];

        if (is_array($lines) && array_is_list($lines) && count($lines) > 0) {
            return array_values(array_filter($lines, static fn (mixed $line): bool => is_array($line)));
        }

        return [[
            'product_family' => $invoice['product_family'] ?? 'Non classe',
            'total' => $invoice['total'] ?? $invoice['total_amount'] ?? $invoice['amount'] ?? 0,
            'quantity' => 1,
        ]];
    }

    private function invoiceDate(array $invoice): string
    {
        $date = $invoice['date'] ?? $invoice['issue_date'] ?? $invoice['created_at'] ?? now()->toDateString();

        return CarbonImmutable::parse($date)->toDateString();
    }

    private function clientId(array $invoice): string
    {
        return (string) (
            $invoice['client_id']
            ?? $invoice['customer_id']
            ?? $invoice['customer_reference']
            ?? 'unknown'
        );
    }

    private function clientName(array $invoice, ?array $client): string
    {
        return (string) (
            $client['name']
            ?? $client['company_name']
            ?? $invoice['client_name']
            ?? $invoice['customer_name']
            ?? 'Client inconnu'
        );
    }

    private function clientStatus(array $invoice, ?array $client, ?CarbonInterface $fromDate): string
    {
        $status = (string) ($client['status'] ?? $invoice['client_status'] ?? 'active');
        $createdAt = $client['created_at'] ?? $invoice['client_created_at'] ?? null;

        if ($createdAt && $fromDate && CarbonImmutable::parse($createdAt)->greaterThanOrEqualTo($fromDate)) {
            return 'new';
        }

        return Str::of($status)->lower()->replace(' ', '_')->toString();
    }

    /**
     * @param  array<string, array<string, mixed>>  $products
     */
    private function productFamily(array $line, array $products): string
    {
        $productId = (string) ($line['product_id'] ?? $line['id'] ?? '');
        $product = $products[$productId] ?? null;

        return (string) (
            $line['product_family']
            ?? $line['family']
            ?? $line['category']
            ?? $product['family']
            ?? $product['product_family']
            ?? $product['category']
            ?? 'Non classe'
        );
    }

    private function lineAmount(array $line, array $invoice): float
    {
        if (isset($line['total']) || isset($line['total_amount'])) {
            return (float) ($line['total'] ?? $line['total_amount']);
        }

        if (isset($line['price']) || isset($line['unit_price'])) {
            return $this->lineQuantity($line) * (float) ($line['price'] ?? $line['unit_price']);
        }

        return (float) ($invoice['total'] ?? $invoice['total_amount'] ?? $invoice['amount'] ?? 0);
    }

    private function lineQuantity(array $line): float
    {
        return (float) ($line['quantity'] ?? $line['qty'] ?? 1);
    }

    private function siteId(array $invoice): ?int
    {
        $siteId = $invoice['site_id'] ?? null;

        return is_numeric($siteId) ? (int) $siteId : null;
    }
}
