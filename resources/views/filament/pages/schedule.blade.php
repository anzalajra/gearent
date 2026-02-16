<x-filament-panels::page>
    <div class="flex items-center space-x-1 mb-4 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg w-fit">
        <button 
            wire:click="setTab('order')" 
            class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'order' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-300' }}"
        >
            By Order
        </button>
        <button 
            wire:click="setTab('unit')" 
            class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === 'unit' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:hover:text-gray-300' }}"
        >
            By Unit
        </button>
    </div>

    @if($activeTab === 'order')
        <div wire:key="tab-order">
            <div class="flex items-center justify-between mb-4">
                <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #f97316;"></div>
                    <span class="text-sm">Quotation</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #3b82f6;"></div>
                    <span class="text-sm">Confirmed</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #22c55e;"></div>
                    <span class="text-sm">Active</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #a855f7;"></div>
                    <span class="text-sm">Completed</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #6b7280;"></div>
                    <span class="text-sm">Cancelled</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded" style="background: #ef4444;"></div>
                    <span class="text-sm">Late Pickup/Return</span>
                </div>
            </div>

            <a href="{{ url('/admin/rentals/create') }}" class="filament-button filament-button-size-md bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition">
                New Rental
            </a>
        </div>

        @livewire(\App\Filament\Widgets\RentalCalendarWidget::class)
        </div>
    @else
        <div wire:key="tab-unit" class="space-y-4">
            @php
                $products = $this->getProductsWithUnitsAndRentals();
            @endphp
            {{-- Search Control --}}
            <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm relative">
                <x-filament::input.wrapper prefix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input
                        wire:model.live.debounce.500ms="search"
                        type="search"
                        placeholder="Search products or units..."
                    />
                </x-filament::input.wrapper>
            </div>

            {{-- Header & Navigation --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Global Product Schedule</h2>
                    <p class="text-xs text-gray-500">Rental schedule for all products and units</p>
                </div>
                
                <div class="flex items-center gap-2 bg-gray-100 dark:bg-white/5 p-1 rounded-lg">
                    <x-filament::button wire:click="previousMonth" icon="heroicon-m-chevron-left" color="gray" size="sm" variant="ghost" />
                    <span class="text-sm font-bold px-4 text-gray-700 dark:text-gray-200">
                        {{ $startDate->format('M Y') }} - {{ $endDate->format('M Y') }}
                    </span>
                    <x-filament::button wire:click="nextMonth" icon="heroicon-m-chevron-right" color="gray" size="sm" variant="ghost" />
                </div>
            </div>

            {{-- Timeline Table --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-white/10 shadow-sm relative">
                <div wire:loading wire:target="search, previousMonth, nextMonth, setTab, gotoPage, nextPage, previousPage, perPage" class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                    <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                        <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <div class="overflow-x-auto overflow-y-hidden rounded-xl">
                    <table class="w-full text-left border-collapse table-fixed min-w-max border-spacing-0">
                        <thead>
                            <tr>
                                <th class="sticky left-0 z-30 p-3 bg-gray-50 dark:bg-gray-800 border-b border-r border-gray-200 dark:border-white/10 w-32 md:w-64 text-xs font-bold uppercase text-gray-600 dark:text-gray-400">
                                    Product / Unit
                                </th>
                                @foreach($days as $day)
                                    <th class="p-2 border-b border-r border-gray-200 dark:border-white/10 text-center min-w-[35px] {{ $day->isToday() ? 'bg-primary-50 dark:bg-primary-900/20' : 'bg-gray-50/50 dark:bg-white/5' }}">
                                        <div class="text-[9px] font-medium text-gray-400">{{ $day->format('D') }}</div>
                                        <div class="text-xs font-bold {{ $day->isToday() ? 'text-primary-600' : 'text-gray-700 dark:text-gray-200' }}">
                                            {{ $day->format('d') }}
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $group)
                                {{-- Product Header Row --}}
                                <tr wire:key="product-header-{{ $group['product']->id }}" class="bg-gray-50 dark:bg-gray-800/50">
                                    <td colspan="{{ count($days) + 1 }}" class="sticky left-0 z-20 p-2 pl-3 border-b border-gray-200 dark:border-white/10 font-bold text-sm text-primary-600 dark:text-primary-400">
                                        {{ $group['product']->name }}
                                    </td>
                                </tr>

                                @foreach($group['units'] as $data)
                                    <tr wire:key="unit-row-{{ $data['unit']->id }}" class="h-12 border-b border-gray-100 dark:border-white/5 last:border-0">
                                        <td class="sticky left-0 z-20 p-3 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-white/10 text-sm text-gray-800 dark:text-gray-200 pl-6">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-mono bg-gray-100 dark:bg-white/10 px-2 py-0.5 rounded text-gray-600 dark:text-gray-400">
                                                    {{ $data['unit']->serial_number }}
                                                </span>
                                            </div>
                                        </td>
                                        
                                        @php
                                            $occupiedDays = [];
                                            foreach($data['rentals'] as $rental) {
                                                $current = $rental['start']->copy()->startOfDay();
                                                $end = $rental['end']->copy()->startOfDay();
                                                while($current <= $end) {
                                                    $occupiedDays[$current->format('Y-m-d')] = $rental;
                                                    $current->addDay();
                                                }
                                            }
                                            $skipDays = 0;
                                        @endphp

                                        @foreach($days as $index => $day)
                                            @if($skipDays > 0)
                                                @php $skipDays--; @endphp
                                                @continue
                                            @endif

                                            @php
                                                $dateStr = $day->format('Y-m-d');
                                                $rental = $occupiedDays[$dateStr] ?? null;
                                                
                                                $colspan = 1;
                                                if ($rental) {
                                                    // Calculate how many subsequent days have the same rental
                                                    $remainingDays = count($days) - $index;
                                                    for ($i = 1; $i < $remainingDays; $i++) {
                                                        $nextDateStr = $days[$index + $i]->format('Y-m-d');
                                                        $nextRental = $occupiedDays[$nextDateStr] ?? null;
                                                        if ($nextRental && $nextRental['id'] === $rental['id']) {
                                                            $colspan++;
                                                        } else {
                                                            break;
                                                        }
                                                    }
                                                    $skipDays = $colspan - 1;
                                                }
                                                
                                                $colorMap = [
                                                    'quotation' => ['bg' => 'bg-orange-500', 'text' => 'text-white'],
                                                    'confirmed' => ['bg' => 'bg-blue-500', 'text' => 'text-white'],
                                                    'active' => ['bg' => 'bg-green-500', 'text' => 'text-white'],
                                                    'completed' => ['bg' => 'bg-purple-500', 'text' => 'text-white'],
                                                    'cancelled' => ['bg' => 'bg-gray-500', 'text' => 'text-white'],
                                                    'late_pickup' => ['bg' => 'bg-red-600', 'text' => 'text-white'],
                                                    'late_return' => ['bg' => 'bg-red-600', 'text' => 'text-white'],
                                                ];
                                                $status = strtolower($rental['status'] ?? '');
                                                $colors = $colorMap[$status] ?? ['bg' => 'bg-gray-100 dark:bg-white/5', 'text' => 'text-transparent'];
                                            @endphp
                                            <td colspan="{{ $colspan }}" class="p-0 border-r border-gray-200 dark:border-white/10 relative {{ $day->isToday() && !$rental ? 'bg-primary-50/20' : '' }}">
                                                @if($rental)
                                                    <div 
                                                        wire:click="mountAction('viewRentalDetails', { rentalId: {{ $rental['id'] }} })"
                                                        class="absolute inset-y-1 left-0 right-0 {{ $colors['bg'] }} z-10 flex items-center px-1 shadow-sm cursor-pointer hover:opacity-80 transition-opacity mx-0.5 rounded-sm"
                                                        title="{{ $rental['code'] }} - {{ $rental['customer'] }} ({{ ucfirst($status) }})"
                                                    >
                                                        <span class="text-[9px] font-bold {{ $colors['text'] }} truncate whitespace-nowrap leading-none px-1">
                                                            {{ $rental['customer'] }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @empty
                                <tr wire:key="empty-state">
                                    <td class="sticky left-0 z-20 p-6 bg-white dark:bg-gray-900 border-r border-b border-gray-200 dark:border-white/10">
                                        <div class="flex flex-col items-center justify-center gap-2 text-gray-500 dark:text-gray-400">
                                            <x-heroicon-o-magnifying-glass class="w-6 h-6" />
                                            <span class="text-xs font-medium text-center">No product / Unit found</span>
                                        </div>
                                    </td>
                                    <td colspan="{{ count($days) }}" class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-white/10"></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-200 dark:border-white/10 flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="w-full md:w-32 shrink-0 order-2 md:order-1">
                        <x-filament::input.wrapper prefix="Per page">
                            <x-filament::input.select wire:model.live="perPage">
                                <option value="15">15</option>
                                <option value="35">35</option>
                                <option value="55">55</option>
                                <option value="75">75</option>
                                <option value="95">95</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <div class="w-full md:w-auto order-1 md:order-2">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-4 text-[10px] font-bold uppercase tracking-wider text-gray-500 bg-white dark:bg-gray-900 p-3 rounded-xl border border-gray-200 dark:border-white/10">
                <span class="mr-2">Status Legend:</span>
                <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-orange-500"></div> Quotation</div>
                <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-blue-500"></div> Confirmed</div>
                <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-green-500"></div> Active</div>
                <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-purple-500"></div> Completed</div>
                <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-gray-500"></div> Cancelled</div>
                <div class="flex items-center gap-1"><div class="w-3 h-3 rounded bg-red-600"></div> Late Pickup/Return</div>
            </div>
        </div>
    @endif
    
    <x-filament-actions::modals />
</x-filament-panels::page>
