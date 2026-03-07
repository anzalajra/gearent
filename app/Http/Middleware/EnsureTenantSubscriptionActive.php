<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionActive
{
    /**
     * Paths always allowed regardless of subscription status.
     */
    protected array $allowedPaths = [
        'admin/subscription-billing',
        'livewire/update',
        'subscription-expired',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            return $next($request);
        }

        // Always allow whitelisted paths
        foreach ($this->allowedPaths as $path) {
            if ($request->is($path.'*') || $request->is($path)) {
                return $next($request);
            }
        }

        // Suspended: full block
        if ($tenant->isSuspended()) {
            return redirect()->route('subscription.expired');
        }

        // Grace period: allow read access, flash warning
        if ($tenant->isInGracePeriod()) {
            $graceTo = $tenant->grace_period_ends_at?->format('d M Y') ?? '-';
            session()->flash('subscription_warning',
                "Subscription Anda telah berakhir. Anda memiliki waktu hingga {$graceTo} untuk memperpanjang sebelum akun disuspend."
            );
        }

        return $next($request);
    }
}
