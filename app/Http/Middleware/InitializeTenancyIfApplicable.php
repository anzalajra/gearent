<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialize tenancy for non-central domain requests.
 *
 * This middleware wraps InitializeTenancyByDomain but only activates
 * when the request domain is NOT a central domain. This allows it to
 * be used as a global middleware without breaking central domain routes.
 *
 * It also skips initialization if tenancy is already initialized
 * (e.g., by AdminPanelProvider's middleware stack).
 */
class InitializeTenancyIfApplicable
{
    public function handle(Request $request, Closure $next): Response
    {
        $centralDomains = config('tenancy.central_domains', []);
        $host = $request->getHost();

        // Skip for central domains — they don't need tenant context
        if (in_array($host, $centralDomains, true)) {
            return $next($request);
        }

        // Skip if tenancy is already initialized (by Filament panel middleware, etc.)
        if (tenancy()->initialized) {
            return $next($request);
        }

        // Delegate to stancl's InitializeTenancyByDomain
        return app(InitializeTenancyByDomain::class)->handle($request, $next);
    }
}
