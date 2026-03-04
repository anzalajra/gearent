<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;

class RentalLimitService
{
    protected static function getTenant(): ?Tenant
    {
        /** @var \App\Models\Tenant|null $tenant */
        $tenant = tenant();

        return $tenant instanceof Tenant ? $tenant : null;
    }

    /**
     * Ensure the current tenant has remaining rental quota for this month.
     *
     * @throws \DomainException
     */
    public static function ensureRentalLimitNotExceeded(): void
    {
        $tenant = self::getTenant();

        if (! $tenant) {
            return;
        }

        self::syncCurrentMonth($tenant);

        // Limit hanya berlaku untuk paket Free dengan limit terdefinisi
        if (! $tenant->isOnFreePlan()) {
            $tenant->save();
            return;
        }

        $limit = $tenant->rentalLimit();
        if ($limit === null) {
            $tenant->save();
            return;
        }

        if ((int) $tenant->current_rental_transactions_month >= $limit) {
            $tenant->save();

            throw new \DomainException(
                'Limit transaksi paket Free Anda sudah habis bulan ini. Silakan upgrade ke paket Basic atau Pro untuk melanjutkan.'
            );
        }

        $tenant->save();
    }

    /**
     * Increment rental counter for the current month.
     */
    public static function incrementRentalCount(): void
    {
        $tenant = self::getTenant();

        if (! $tenant) {
            return;
        }

        self::syncCurrentMonth($tenant);

        $tenant->current_rental_transactions_month = (int) $tenant->current_rental_transactions_month + 1;
        $tenant->save();
    }

    /**
     * Get remaining quota for current month (null when unlimited).
     */
    public static function remainingQuota(): ?int
    {
        $tenant = self::getTenant();

        if (! $tenant) {
            return null;
        }

        self::syncCurrentMonth($tenant);

        return $tenant->remainingRentalTransactions();
    }

    /**
     * Whether we should show an upgrade warning banner (<= 3 left, > 0).
     */
    public static function shouldShowUpgradeWarning(): bool
    {
        $tenant = self::getTenant();

        if (! $tenant || ! $tenant->isOnFreePlan()) {
            return false;
        }

        $limit = $tenant->rentalLimit();

        if ($limit === null) {
            return false;
        }

        self::syncCurrentMonth($tenant);

        $remaining = $tenant->remainingRentalTransactions();

        return $remaining !== null && $remaining > 0 && $remaining <= 3;
    }

    protected static function syncCurrentMonth(Tenant $tenant): void
    {
        $currentMonth = now()->format('Y-m');

        if ($tenant->current_rental_month !== $currentMonth) {
            $tenant->current_rental_month = $currentMonth;
            $tenant->current_rental_transactions_month = 0;
        }
    }
}

