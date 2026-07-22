<?php

namespace Modules\CrmStats\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BillingApiService
{
    private ?string $lastError = null;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getClients(array $filters = []): array
    {
        return $this->fetchPaginated('clients', $filters);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getInvoices(array $filters = []): array
    {
        return $this->fetchPaginated('invoices', $filters);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getProducts(array $filters = []): array
    {
        return $this->fetchPaginated('products', $filters);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getInvoiceLines(string|int $invoiceId): array
    {
        return $this->fetchPaginated("invoices/{$invoiceId}/lines");
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPaginated(string $endpoint, array $filters = []): array
    {
        $this->lastError = null;

        $filters = array_filter($filters, static fn (mixed $value): bool => $value !== null && $value !== '');
        $cacheKey = 'crm-stats:billing-api:'.sha1($endpoint.serialize($filters));

        try {
            $items = $this->requestAllPages($endpoint, $filters);

            Cache::put($cacheKey, $items, now()->addDays(7));
            Cache::forget('crm-stats:last-api-error');

            return $items;
        } catch (Throwable $throwable) {
            $this->lastError = $throwable->getMessage();

            Log::warning('Billing API unavailable, using cached payload when possible.', [
                'endpoint' => $endpoint,
                'message' => $throwable->getMessage(),
            ]);

            Cache::put('crm-stats:last-api-error', [
                'message' => $throwable->getMessage(),
                'endpoint' => $endpoint,
                'occurred_at' => now()->toIso8601String(),
            ], now()->addDay());

            $cachedItems = Cache::get($cacheKey);

            return is_array($cachedItems) ? $cachedItems : [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function requestAllPages(string $endpoint, array $filters): array
    {
        $page = 1;
        $pageSize = max(1, (int) config('crm-stats.billing_api.page_size', 100));
        $items = [];

        do {
            $payload = $this->client()
                ->get($endpoint, [
                    ...$filters,
                    'page' => $page,
                    'limit' => $pageSize,
                ])
                ->throw()
                ->json();

            if (! is_array($payload)) {
                break;
            }

            $pageItems = $this->extractItems($payload);
            $items = [...$items, ...$pageItems];

            $hasNextPage = $this->hasNextPage($payload, $page, count($pageItems), $pageSize);
            $page++;
        } while ($hasNextPage);

        return $items;
    }

    private function client(): PendingRequest
    {
        $baseUrl = (string) config('crm-stats.billing_api.url');
        $apiKey = (string) config('crm-stats.billing_api.key');

        if (blank($baseUrl) || blank($apiKey)) {
            throw new RuntimeException('Billing API is not configured.');
        }

        return Http::baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withToken($apiKey)
            ->withHeaders(['X-API-Key' => $apiKey])
            ->timeout((int) config('crm-stats.billing_api.timeout', 30))
            ->connectTimeout((int) config('crm-stats.billing_api.connect_timeout', 5))
            ->retry(
                max(1, (int) config('crm-stats.billing_api.retry_times', 3)),
                max(0, (int) config('crm-stats.billing_api.retry_sleep_ms', 250)),
            );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractItems(array $payload): array
    {
        $items = Arr::get($payload, 'data', Arr::get($payload, 'items', $payload));

        if (! is_array($items)) {
            return [];
        }

        if (! array_is_list($items)) {
            return [];
        }

        return array_values(array_filter(
            $items,
            static fn (mixed $item): bool => is_array($item),
        ));
    }

    private function hasNextPage(array $payload, int $page, int $count, int $pageSize): bool
    {
        if (filled(Arr::get($payload, 'links.next')) || filled(Arr::get($payload, 'next_page_url'))) {
            return true;
        }

        $lastPage = Arr::get($payload, 'meta.last_page');
        $currentPage = Arr::get($payload, 'meta.current_page', $page);

        if (is_numeric($lastPage) && is_numeric($currentPage)) {
            return (int) $currentPage < (int) $lastPage;
        }

        return $count === $pageSize;
    }
}
