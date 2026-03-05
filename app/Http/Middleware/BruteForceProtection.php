<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BruteForceProtection
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Check if IP is blocked
        if (Cache::has("blocked_ip:{$ip}")) {
            abort(429, 'Terlalu banyak permintaan. Silakan coba lagi nanti.');
        }

        // Track requests per minute
        $minuteKey = "request_count:{$ip}:".now()->format('Y-m-d-H-i');
        $minuteCount = (int) Cache::get($minuteKey, 0);

        // Track requests per 5 minutes
        $fiveMinKey = "request_burst:{$ip}:".now()->format('Y-m-d-H').'-'.(int) (now()->minute / 5);
        $fiveMinCount = (int) Cache::get($fiveMinKey, 0);

        // Block IP for 15 minutes if > 30 requests in 5 minutes
        if ($fiveMinCount > 30) {
            Cache::put("blocked_ip:{$ip}", true, now()->addMinutes(15));
            abort(429, 'Aktivitas mencurigakan terdeteksi. IP Anda diblokir sementara selama 15 menit.');
        }

        // Return 429 if > 10 requests in 1 minute
        if ($minuteCount > 10) {
            abort(429, 'Terlalu banyak permintaan. Coba lagi dalam beberapa saat.');
        }

        Cache::put($minuteKey, $minuteCount + 1, 60);
        Cache::put($fiveMinKey, $fiveMinCount + 1, 300);

        return $next($request);
    }
}
