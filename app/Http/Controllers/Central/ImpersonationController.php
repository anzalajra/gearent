<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImpersonationController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant)
    {
        // Ensure tenant has domains
        if ($tenant->domains->isEmpty()) {
            abort(404, 'Tenant has no domains configured.');
        }

        $user = null;

        $tenant->run(function () use (&$user) {
            // Ensure is_system_admin column exists
            if (! Schema::hasColumn('users', 'is_system_admin')) {
                Schema::table('users', function ($table) {
                    $table->boolean('is_system_admin')->default(false);
                });
            }

            // Find or create hidden system admin user
            $user = User::where('is_system_admin', true)->first();

            if (! $user) {
                $user = User::create([
                    'name' => 'Zewalo Admin',
                    'email' => 'system@zewalo.internal',
                    'password' => Str::random(64),
                    'is_system_admin' => true,
                ]);
                $user->assignRole('super_admin');
            }
        });

        $token = tenancy()->impersonate(
            $tenant,
            (string) $user->id,
            '/admin',
            'web'
        );

        $domain = $tenant->domains->first()->domain;
        $scheme = app()->environment('production') ? 'https' : 'http';
        $url = "{$scheme}://{$domain}/impersonate/{$token->token}";

        return redirect()->away($url);
    }
}
