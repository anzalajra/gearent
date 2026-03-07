<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case Qris = 'qris';
    case VirtualAccount = 'virtual_account';
    case EWallet = 'e_wallet';
    case RetailOutlet = 'retail_outlet';
    case CreditCard = 'credit_card';

    public function getLabel(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::VirtualAccount => 'Virtual Account',
            self::EWallet => 'E-Wallet',
            self::RetailOutlet => 'Retail Outlet',
            self::CreditCard => 'Kartu Kredit',
        };
    }

    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
