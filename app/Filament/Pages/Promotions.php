<?php

namespace App\Filament\Pages;

use App\Enums\TenantFeature;
use App\Filament\Concerns\ChecksTenantFeature;
use App\Models\DailyDiscount;
use App\Models\DatePromotion;
use App\Models\Discount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use UnitEnum;

class Promotions extends Page
{
    use ChecksTenantFeature;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $navigationLabel = 'Promotions';

    protected static ?string $title = 'Promotions';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'promotions';

    public static function shouldRegisterNavigation(): bool
    {
        return static::tenantHasFeature(TenantFeature::Promotion);
    }

    public static function canAccess(): bool
    {
        return static::tenantHasFeature(TenantFeature::Promotion);
    }

    protected string $view = 'filament.pages.promotions';

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public function getPromotions(): Collection
    {
        // Collect all promotions from different models
        $promotions = collect();

        // Code Discounts
        $discounts = Discount::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'type' => 'discount',
                'type_label' => 'Kode Diskon',
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->type === 'percentage' ? $d->value.'%' : 'Rp '.number_format($d->value, 0, ',', '.'),
                'validity' => $d->end_date ? $d->end_date->format('d M Y') : '-',
                'is_active' => $d->is_active,
                'is_expired' => $d->end_date?->isPast() ?? false,
                'edit_url' => route('filament.admin.resources.discount-codes.edit', $d),
                'color' => 'primary',
            ]);

        // Daily Discounts
        $dailyDiscounts = DailyDiscount::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'type' => 'daily',
                'type_label' => 'Diskon Harian',
                'name' => $d->name,
                'code' => null,
                'description' => "Sewa {$d->min_days} hari, gratis {$d->free_days} hari",
                'validity' => $d->end_date ? $d->end_date->format('d M Y') : '-',
                'is_active' => $d->is_active,
                'is_expired' => $d->end_date?->isPast() ?? false,
                'edit_url' => route('filament.admin.resources.daily-discounts.edit', $d),
                'color' => 'success',
            ]);

        // Date Promotions
        $datePromotions = DatePromotion::query()
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'type' => 'date',
                'type_label' => 'Promo Tanggal',
                'name' => $d->name,
                'code' => null,
                'description' => ($d->type === 'percentage' ? $d->value.'%' : 'Rp '.number_format($d->value, 0, ',', '.')).' - '.$d->promo_date->format('d M'),
                'validity' => $d->recurring_yearly ? 'Setiap Tahun' : $d->promo_date->format('d M Y'),
                'is_active' => $d->is_active,
                'is_expired' => ! $d->recurring_yearly && $d->promo_date->isPast(),
                'edit_url' => route('filament.admin.resources.date-promotions.edit', $d),
                'color' => 'warning',
            ]);

        // Filter by type
        if ($this->typeFilter === 'discount') {
            return $discounts;
        } elseif ($this->typeFilter === 'daily') {
            return $dailyDiscounts;
        } elseif ($this->typeFilter === 'date') {
            return $datePromotions;
        }

        return $promotions->merge($discounts)->merge($dailyDiscounts)->merge($datePromotions);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Add New Promotion')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalWidth('md')
                ->modalHeading('Choose Promotion Type')
                ->modalDescription('Select the type of promotion you want to create.')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cancel')
                ->modalContent(view('filament.pages.promotions-create-modal')),
        ];
    }

    public function updatedSearch()
    {
        // Livewire reactivity handles this
    }

    public function updatedTypeFilter()
    {
        // Livewire reactivity handles this
    }

    public function updatedStatusFilter()
    {
        // Livewire reactivity handles this
    }
}
