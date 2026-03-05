<?php

namespace App\Enums;

enum TenantFeature: string
{
    case Finance = 'finance';
    case Deliveries = 'deliveries';
    case CustomerVerification = 'customer_verification';
    case QuotationInvoice = 'quotation_invoice';
    case Promotion = 'promotion';
    case InventoryQc = 'inventory_qc';
    case InventoryWarehouse = 'inventory_warehouse';
    case ProductUnit = 'product_unit';
    case Storefront = 'storefront';
    case PagePost = 'page_post';
    case EmailNotification = 'email_notification';

    public function getLabel(): string
    {
        return match ($this) {
            self::Finance => 'Finance (Keuangan, Pengaturan & Pajak)',
            self::Deliveries => 'Deliveries (Pengiriman)',
            self::CustomerVerification => 'Customer Verification (Verifikasi Dokumen)',
            self::QuotationInvoice => 'Quotation & Invoice',
            self::Promotion => 'Promotion (Promosi & Diskon)',
            self::InventoryQc => 'Inventory Maintenance QC',
            self::InventoryWarehouse => 'Inventory Warehouse (Gudang)',
            self::ProductUnit => 'Product Unit (Unit Produk)',
            self::Storefront => 'Storefront (Etalase Toko)',
            self::PagePost => 'Page & Post (CMS)',
            self::EmailNotification => 'Email Notification (Notifikasi Email)',
        };
    }

    /**
     * Get all features as options array for Filament forms.
     */
    public static function toOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
