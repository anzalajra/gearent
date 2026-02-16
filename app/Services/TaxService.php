<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

class TaxService
{
    /**
     * Calculate Tax (PPN/VAT/GST) based on Tax Mode and Customer.
     * 
     * @param float $amount The base amount (can be inclusive or exclusive)
     * @param bool $isTaxable Whether the item is taxable
     * @param bool $priceIncludesTax Whether the price includes tax
     * @param User|null $customer The customer (optional, for international rates/exemptions)
     * @return array ['tax_base', 'tax_amount', 'tax_rate', 'tax_name', 'total']
     */
    public static function calculateTax(float $amount, bool $isTaxable, bool $priceIncludesTax, ?User $customer = null): array
    {
        // Global Tax Check
        $taxEnabled = filter_var(Setting::get('tax_enabled', true), FILTER_VALIDATE_BOOLEAN);

        // Default result (No Tax)
        $result = [
            'tax_base' => $amount,
            'tax_amount' => 0,
            'tax_rate' => 0,
            'tax_name' => 'Tax',
            'total' => $amount,
        ];

        if (!$taxEnabled) {
            $result['tax_name'] = 'Tax Disabled';
            return $result;
        }

        $taxMode = Setting::get('tax_mode', 'indonesia');

        if (!$isTaxable) {
            return $result;
        }

        // Check for Tax Exempt Customer
        if ($customer && $customer->is_tax_exempt) {
            $result['tax_name'] = 'Tax Exempt';
            return $result;
        }

        $taxRate = 0;
        $taxName = 'Tax';

        if ($taxMode === 'international') {
            // International Logic
            $customerCountry = $customer ? ($customer->tax_country ?? 'ID') : 'ID';
            $rates = Setting::get('international_tax_rates', '[]');
            
            if (is_string($rates)) {
                $rates = json_decode($rates, true) ?? [];
            }
            
            // Find rate for country
            foreach ($rates as $r) {
                if (($r['country_code'] ?? '') === $customerCountry) {
                    $taxRate = (float) ($r['rate'] ?? 0);
                    $taxName = $r['tax_name'] ?? 'Tax';
                    break;
                }
            }
        } else {
            // Indonesia Logic
            $isPkp = (bool) Setting::get('is_pkp', false);
            
            if (!$isPkp) {
                return $result;
            }

            $taxRate = (float) Setting::get('ppn_rate', 11);
            $taxName = 'PPN';
        }

        // Calculate Tax
        $result['tax_rate'] = $taxRate;
        $result['tax_name'] = $taxName;

        if ($priceIncludesTax) {
            // Inclusive: TaxBase = Amount / (1 + Rate/100)
            $dpp = $amount / (1 + ($taxRate / 100));
            $tax = $amount - $dpp;
            
            $result['tax_base'] = round($dpp, 2);
            $result['tax_amount'] = round($tax, 2);
            $result['total'] = $amount;
        } else {
            // Exclusive: TaxBase = Amount
            $dpp = $amount;
            $tax = $dpp * ($taxRate / 100);
            
            $result['tax_base'] = round($dpp, 2);
            $result['tax_amount'] = round($tax, 2);
            $result['total'] = $dpp + $tax;
        }

        return $result;
    }

    /**
     * @deprecated Use calculateTax() instead.
     */
    public static function calculatePPN(float $amount, bool $isTaxable, bool $priceIncludesTax): array
    {
        $result = self::calculateTax($amount, $isTaxable, $priceIncludesTax);
        return [
            'tax_base' => $result['tax_base'],
            'ppn_amount' => $result['tax_amount'],
            'ppn_rate' => $result['tax_rate'],
            'total' => $result['total'],
        ];
    }

    /**
     * Calculate PPh 23 Withholding Tax.
     * 
     * @param float $dpp The Tax Base (Dasar Pengenaan Pajak)
     * @param User $customer The customer/client
     * @return float The withholding tax amount (to be deducted from payment or recorded as tax credit)
     */
    public static function calculatePPh23(float $dpp, User $customer): float
    {
        // Corporate or Government entities withhold 2% PPh 23
        if (in_array($customer->tax_type, ['corporate', 'government'])) {
            return round($dpp * 0.02, 2);
        }

        return 0.0;
    }

    /**
     * Calculate PPh Final (UMKM 0.5%) for a given turnover.
     * 
     * @param float $turnover The gross turnover (omzet kotor)
     * @return float The tax amount
     */
    public static function calculatePPhFinal(float $turnover): float
    {
        $rate = (float) Setting::get('pph_final_rate', 0.5);
        return round($turnover * ($rate / 100), 2);
    }
}
