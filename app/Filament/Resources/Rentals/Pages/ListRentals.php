<?php

namespace App\Filament\Resources\Rentals\Pages;

use App\Filament\Resources\Rentals\RentalResource;
use App\Models\Rental;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListRentals extends ListRecords
{
    protected static string $resource = RentalResource::class;

    public function mount(): void
    {
        $this->updateLateStatuses();
        
        parent::mount();
    }

    protected function updateLateStatuses(): void
    {
        $now = now();

        // Update late pickups - gunakan DB::table untuk bypass model events
        DB::table('rentals')
            ->where('status', 'pending')
            ->where('start_date', '<', $now)
            ->update(['status' => 'late_pickup', 'updated_at' => $now]);

        // Update late returns
        DB::table('rentals')
            ->where('status', 'active')
            ->where('end_date', '<', $now)
            ->update(['status' => 'late_return', 'updated_at' => $now]);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}