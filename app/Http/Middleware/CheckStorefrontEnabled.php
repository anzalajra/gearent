<?php

namespace App\Http\Middleware;

use App\Enums\TenantFeature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStorefrontEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant && ! $tenant->hasFeature(TenantFeature::Storefront)) {
            abort(403, 'Etalase toko tidak aktif untuk tenant ini.');
        }

        return $next($request);
    }
}
