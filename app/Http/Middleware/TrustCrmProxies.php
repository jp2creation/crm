<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies;

class TrustCrmProxies extends TrustProxies
{
    protected function proxies()
    {
        return config('crm.security.trusted_proxies') ?: null;
    }

    protected function headers()
    {
        $headers = config('crm.security.trusted_proxy_headers');

        return is_int($headers) ? $headers : parent::headers();
    }
}
