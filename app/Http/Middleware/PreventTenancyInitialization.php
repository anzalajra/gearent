<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to prevent tenancy initialization for central panel routes.
 * 
 * This ensures that the central admin panel ALWAYS uses the central database
 * connection, regardless of any domain-based tenant identification.
 */
class PreventTenancyInitialization
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force the default database connection to 'central'
        Config::set('database.default', 'central');
        
        // Disable tenancy identification for this request
        // This prevents DatabaseTenancyBootstrapper from switching connections
        Config::set('tenancy.bootstrappers', []);
        
        return $next($request);
    }
}
