<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\CrmStats\Services\BillingApiService;
use Tests\TestCase;

class CrmStatsBillingApiServiceTest extends TestCase
{
    public function test_it_fetches_paginated_billing_api_payloads_with_authentication(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://billing.test/clients*' => Http::sequence()
                ->push([
                    'data' => [
                        ['id' => 'C1', 'name' => 'Client A'],
                    ],
                    'meta' => ['current_page' => 1, 'last_page' => 2],
                ])
                ->push([
                    'data' => [
                        ['id' => 'C2', 'name' => 'Client B'],
                    ],
                    'meta' => ['current_page' => 2, 'last_page' => 2],
                ]),
        ]);

        config([
            'crm-stats.billing_api.url' => 'https://billing.test',
            'crm-stats.billing_api.key' => 'secret-key',
            'crm-stats.billing_api.page_size' => 1,
        ]);

        $clients = app(BillingApiService::class)->getClients(['updated_at' => '2026-07-01']);

        $this->assertSame(['C1', 'C2'], array_column($clients, 'id'));

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            return $request->hasHeader('Authorization', 'Bearer secret-key')
                && str_contains($request->url(), 'updated_at=2026-07-01');
        });
    }

    public function test_it_uses_cached_payload_when_api_is_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://billing.test/products*' => Http::response([
                'data' => [
                    ['id' => 'P1', 'family' => 'Peinture'],
                ],
            ]),
        ]);

        config([
            'crm-stats.billing_api.url' => 'https://billing.test',
            'crm-stats.billing_api.key' => 'secret-key',
        ]);

        $service = app(BillingApiService::class);
        $this->assertSame('P1', $service->getProducts()[0]['id']);

        Http::fake([
            'https://billing.test/products*' => Http::failedConnection(),
        ]);

        $this->assertSame('P1', $service->getProducts()[0]['id']);
        $this->assertNotNull($service->lastError());
    }
}
