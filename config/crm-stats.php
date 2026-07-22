<?php

return [
    'name' => 'CrmStats',
    'billing_api' => [
        'url' => env('BILLING_API_URL'),
        'key' => env('BILLING_API_KEY'),
        'timeout' => (int) env('BILLING_API_TIMEOUT', 30),
        'connect_timeout' => (int) env('BILLING_API_CONNECT_TIMEOUT', 5),
        'retry_times' => (int) env('BILLING_API_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('BILLING_API_RETRY_SLEEP_MS', 250),
        'page_size' => (int) env('BILLING_API_PAGE_SIZE', 100),
    ],
    'lost_client_months' => (int) env('CRM_STATS_LOST_CLIENT_MONTHS', 6),
    'cache_ttl' => (int) env('CRM_STATS_CACHE_TTL', 900),
];
