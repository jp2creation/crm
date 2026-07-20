<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts;

class TrustCrmHosts extends TrustHosts
{
    public function hosts()
    {
        $hosts = config('crm.security.trusted_hosts', []);

        if ((bool) config('crm.security.trusted_host_subdomains', true)) {
            $hosts[] = $this->allSubdomainsOfApplicationUrl();
        }

        return array_values(array_filter($hosts));
    }
}
