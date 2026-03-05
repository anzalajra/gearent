<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

class TenantLivewireMiddleware
{
    /**
     * Handle an incoming request for Livewire updates.
     * It ensures tenancy is only initialized if the request comes from a tenant domain.
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        $centralDomains = config('tenancy.central_domains', []);
        
        // If the request is from a central domain, skip tenancy initialization
        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }
        
        // Otherwise, initialize tenancy (so Livewire components on tenant domains work)
        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}
