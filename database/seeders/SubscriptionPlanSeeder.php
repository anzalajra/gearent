<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Paket gratis untuk memulai bisnis rental Anda',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'IDR',
                'max_users' => 1,
                'max_products' => 10,
                'max_storage_mb' => 100, // 100MB
                'max_domains' => 1,
                'features' => [
                    ['feature' => 'Maksimal 10 produk'],
                    ['feature' => '1 pengguna'],
                    ['feature' => 'Laporan dasar'],
                    ['feature' => 'Support email'],
                ],
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Paket dasar untuk bisnis rental yang berkembang',
                'price_monthly' => 99000,
                'price_yearly' => 999000,
                'currency' => 'IDR',
                'max_users' => 3,
                'max_products' => 100,
                'max_storage_mb' => 1024, // 1GB
                'max_domains' => 1,
                'features' => [
                    ['feature' => 'Maksimal 100 produk'],
                    ['feature' => '3 pengguna'],
                    ['feature' => 'Laporan lengkap'],
                    ['feature' => 'Invoice otomatis'],
                    ['feature' => 'Support prioritas'],
                ],
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Paket profesional untuk bisnis rental skala besar',
                'price_monthly' => 299000,
                'price_yearly' => 2999000,
                'currency' => 'IDR',
                'max_users' => 10,
                'max_products' => 1000,
                'max_storage_mb' => 10240, // 10GB
                'max_domains' => 3,
                'features' => [
                    ['feature' => 'Produk unlimited'],
                    ['feature' => '10 pengguna'],
                    ['feature' => 'Laporan lengkap & analitik'],
                    ['feature' => 'Invoice otomatis'],
                    ['feature' => 'Integrasi API'],
                    ['feature' => 'Custom domain'],
                    ['feature' => 'Support 24/7'],
                    ['feature' => 'Backup otomatis'],
                ],
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
