<x-filament-panels::page>
    <div x-data="{ activeTab: @entangle('activeTab') }" class="space-y-4">
        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
                @foreach([
                    'profit_loss' => 'Profit & Loss',
                    'balance_sheet' => 'Balance Sheet',
                    'cash_flow' => 'Cash Flow',
                    'ar_aging' => 'AR Aging',
                    'trial_balance' => 'Trial Balance',
                    'assets' => 'Asset Reports',
                    'customers' => 'Customer Reports',
                    'tax' => 'Tax Reports'
                ] as $key => $label)
                    <button 
                        @click="activeTab = '{{ $key }}'"
                        :class="activeTab === '{{ $key }}' 
                            ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' 
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors duration-200"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        <!-- Profit & Loss -->
        <div x-show="activeTab === 'profit_loss'" style="display: none;">
            @php $plData = $this->getDetailedProfitLossData(); @endphp
            <x-filament::section>
                <div class="relative">
                    <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                        <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                            <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Profit & Loss Statement</h3>
                    
                    <div class="flex gap-2">
                        <x-filament::dropdown>
                            <x-slot name="trigger">
                                <x-filament::button icon="heroicon-m-arrow-down-tray" color="gray">
                                    Export
                                </x-filament::button>
                            </x-slot>

                            <x-filament::dropdown.list>
                                <x-filament::dropdown.list.item 
                                    wire:click="exportReport('profit_loss', 'pdf')" 
                                    icon="heroicon-m-document-text"
                                >
                                    Export PDF
                                </x-filament::dropdown.list.item>
                                
                                <x-filament::dropdown.list.item 
                                    wire:click="exportReport('profit_loss', 'csv')" 
                                    icon="heroicon-m-table-cells"
                                >
                                    Export CSV
                                </x-filament::dropdown.list.item>
                            </x-filament::dropdown.list>
                        </x-filament::dropdown>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Total Revenue -->
                        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/20">
                                    <x-heroicon-o-currency-dollar class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Revenue</p>
                                    <p class="text-l font-bold text-gray-900 dark:text-white">
                                        {{ Number::currency($plData['revenue']['total'], 'IDR') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Gross Profit -->
                        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
                                    <x-heroicon-o-chart-bar class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gross Profit</p>
                                    <p class="text-l font-bold text-gray-900 dark:text-white">
                                        {{ Number::currency($plData['gross_profit'], 'IDR') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Expenses -->
                        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/20">
                                    <x-heroicon-o-banknotes class="h-6 w-6 text-red-600 dark:text-red-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Expenses</p>
                                    <p class="text-l font-bold text-gray-900 dark:text-white">
                                        {{ Number::currency($plData['expenses']['total'], 'IDR') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Net Profit -->
                        <div class="p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $plData['net_profit'] >= 0 ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                                    @if($plData['net_profit'] >= 0)
                                        <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    @else
                                        <x-heroicon-o-arrow-trending-down class="h-6 w-6 text-red-600 dark:text-red-400" />
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Profit</p>
                                    <p class="text-l font-bold {{ $plData['net_profit'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ Number::currency($plData['net_profit'], 'IDR') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Table -->
                    <div class="overflow-x-auto border rounded-lg dark:border-gray-700">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                                <!-- Revenue Section -->
                                <tr class="bg-gray-50/50 dark:bg-gray-800/50">
                                    <td colspan="2" class="px-6 py-2 font-bold text-gray-700 dark:text-gray-300">REVENUE</td>
                                </tr>
                                @foreach($plData['revenue']['items'] as $item)
                                    <tr>
                                        <td class="px-6 py-2 pl-10">{{ $item['name'] }}</td>
                                        <td class="px-6 py-2 text-right">{{ Number::currency($item['amount'], 'IDR') }}</td>
                                    </tr>
                                @endforeach
                                <tr class="font-bold bg-green-50/30 dark:bg-green-900/10">
                                    <td class="px-6 py-3 pl-10">Total Revenue</td>
                                    <td class="px-6 py-3 text-right text-green-600 dark:text-green-400">{{ Number::currency($plData['revenue']['total'], 'IDR') }}</td>
                                </tr>

                                <!-- COGS Section -->
                                <tr class="bg-gray-50/50 dark:bg-gray-800/50">
                                    <td colspan="2" class="px-6 py-2 font-bold text-gray-700 dark:text-gray-300">COST OF REVENUE (HPP)</td>
                                </tr>
                                @if(count($plData['cogs']['items']) > 0)
                                    @foreach($plData['cogs']['items'] as $item)
                                        <tr>
                                            <td class="px-6 py-2 pl-10">{{ $item['name'] }}</td>
                                            <td class="px-6 py-2 text-right text-red-500">({{ Number::currency($item['amount'], 'IDR') }})</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="px-6 py-2 pl-10 text-gray-500 italic">No cost of revenue recorded</td>
                                        <td class="px-6 py-2 text-right">-</td>
                                    </tr>
                                @endif
                                <tr class="font-bold border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-6 py-3 pl-10">Gross Profit</td>
                                    <td class="px-6 py-3 text-right {{ $plData['gross_profit'] >= 0 ? 'text-gray-900 dark:text-white' : 'text-red-600' }}">
                                        {{ Number::currency($plData['gross_profit'], 'IDR') }}
                                    </td>
                                </tr>

                                <!-- Expenses Section -->
                                <tr class="bg-gray-50/50 dark:bg-gray-800/50">
                                    <td colspan="2" class="px-6 py-2 font-bold text-gray-700 dark:text-gray-300">OPERATING EXPENSES</td>
                                </tr>
                                @if(count($plData['expenses']['items']) > 0)
                                    @foreach($plData['expenses']['items'] as $item)
                                        <tr>
                                            <td class="px-6 py-2 pl-10">{{ $item['name'] }}</td>
                                            <td class="px-6 py-2 text-right text-red-500">({{ Number::currency($item['amount'], 'IDR') }})</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="px-6 py-2 pl-10 text-gray-500 italic">No operating expenses recorded</td>
                                        <td class="px-6 py-2 text-right">-</td>
                                    </tr>
                                @endif
                                <tr class="font-bold border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-6 py-3 pl-10">Total Operating Expenses</td>
                                    <td class="px-6 py-3 text-right text-red-600 dark:text-red-400">
                                        ({{ Number::currency($plData['expenses']['total'], 'IDR') }})
                                    </td>
                                </tr>

                                <!-- Net Profit Section -->
                                <tr class="bg-gray-100 dark:bg-gray-800 border-t-2 border-gray-300 dark:border-gray-600 text-lg">
                                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">NET PROFIT</td>
                                    <td class="px-6 py-4 text-right font-bold {{ $plData['net_profit'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ Number::currency($plData['net_profit'], 'IDR') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Balance Sheet -->
        <div x-show="activeTab === 'balance_sheet'" style="display: none;">
            @php $bsData = $this->getBalanceSheetData(); @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Assets -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <h3 class="text-lg font-medium mb-4">Assets</h3>
                    <div class="space-y-2">
                        @foreach($bsData['assets'] as $name => $amount)
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">{{ $name }}</span>
                                <span class="font-medium">{{ Number::currency($amount, 'IDR') }}</span>
                            </div>
                        @endforeach
                        <div class="border-t pt-2 mt-2 flex justify-between font-bold text-lg">
                            <span>Total Assets</span>
                            <span>{{ Number::currency($bsData['total_assets'], 'IDR') }}</span>
                        </div>
                    </div>
                    </div>
                </x-filament::section>

                <!-- Liabilities & Equity -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <h3 class="text-lg font-medium mb-4">Liabilities & Equity</h3>
                    <div class="space-y-2">
                        <h4 class="font-medium text-gray-500">Liabilities</h4>
                        @foreach($bsData['liabilities'] as $name => $amount)
                            <div class="flex justify-between pl-4">
                                <span class="text-gray-600 dark:text-gray-400">{{ $name }}</span>
                                <span class="font-medium">{{ Number::currency($amount, 'IDR') }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between font-bold border-t border-dashed pt-1">
                            <span>Total Liabilities</span>
                            <span>{{ Number::currency($bsData['total_liabilities'], 'IDR') }}</span>
                        </div>

                        <h4 class="font-medium text-gray-500 mt-4">Equity</h4>
                        <div class="flex justify-between pl-4">
                            <span class="text-gray-600 dark:text-gray-400">Owner's Equity</span>
                            <span class="font-medium">{{ Number::currency($bsData['equity'], 'IDR') }}</span>
                        </div>
                        
                        <div class="border-t-2 pt-2 mt-4 flex justify-between font-bold text-lg">
                            <span>Total Liabilities & Equity</span>
                            <span>{{ Number::currency($bsData['total_liabilities'] + $bsData['equity'], 'IDR') }}</span>
                        </div>
                    </div>
                    </div>
                </x-filament::section>


            </div>
        </div>

        <!-- Cash Flow -->
        <div x-show="activeTab === 'cash_flow'" style="display: none;">
            @php $cfData = $this->getCashFlowData(); @endphp
            <x-filament::section>
                <div class="relative">
                    <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                        <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                            <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                <h3 class="text-lg font-medium mb-4">Cash Flow Statement</h3>
                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-500 mb-2">Operating Activities</h4>
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Cash Inflow</span>
                            <span class="text-green-600 font-medium">{{ Number::currency($cfData['operating_activities']['Inflow'], 'IDR') }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Cash Outflow</span>
                            <span class="text-red-600 font-medium">({{ Number::currency($cfData['operating_activities']['Outflow'], 'IDR') }})</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="font-bold text-lg">Net Cash Flow</span>
                        <span class="font-bold text-xl {{ $cfData['net_cash_flow'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ Number::currency($cfData['net_cash_flow'], 'IDR') }}
                        </span>
                    </div>
                </div>
                </div>
            </x-filament::section>
        </div>

        <!-- AR Aging -->
        <div x-show="activeTab === 'ar_aging'" style="display: none;">
            @php $arData = $this->getARAgingData(); @endphp
            <div class="space-y-6">
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <h3 class="text-lg font-medium mb-4">Accounts Receivable Aging</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-sm text-green-700 dark:text-green-400">Current (0-30 days)</div>
                            <div class="text-xl font-bold text-green-800 dark:text-green-300">{{ Number::currency($arData['summary']['0-30'], 'IDR') }}</div>
                        </div>
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="text-sm text-yellow-700 dark:text-yellow-400">31-60 days</div>
                            <div class="text-xl font-bold text-yellow-800 dark:text-yellow-300">{{ Number::currency($arData['summary']['31-60'], 'IDR') }}</div>
                        </div>
                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg">
                            <div class="text-sm text-orange-700 dark:text-orange-400">61-90 days</div>
                            <div class="text-xl font-bold text-orange-800 dark:text-orange-300">{{ Number::currency($arData['summary']['61-90'], 'IDR') }}</div>
                        </div>
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <div class="text-sm text-red-700 dark:text-red-400">90+ days</div>
                            <div class="text-xl font-bold text-red-800 dark:text-red-300">{{ Number::currency($arData['summary']['90+'], 'IDR') }}</div>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="text-sm text-gray-700 dark:text-gray-400">Total Outstanding</div>
                            <div class="text-xl font-bold text-gray-900 dark:text-white">{{ Number::currency($arData['total_outstanding'], 'IDR') }}</div>
                        </div>
                    </div>
                    </div>
                </x-filament::section>

                <!-- Detailed AR Table -->
                <div class="overflow-x-auto border rounded-lg dark:border-gray-700">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 uppercase text-xs">
                            <tr>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Invoice #</th>
                                <th class="px-6 py-3">Due Date</th>
                                <th class="px-6 py-3 text-right">Days Overdue</th>
                                <th class="px-6 py-3 text-right">Amount Due</th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                            @php $arPaginator = $this->getARAgingPaginator(); @endphp
                            @foreach($arPaginator as $invoice)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-4 font-medium">{{ $invoice->user->name ?? 'Unknown' }}</td>
                                    <td class="px-6 py-4">{{ $invoice->number }}</td>
                                    <td class="px-6 py-4">{{ $invoice->due_date->format('d M Y') }}</td>
                                    <td class="px-6 py-4 text-right">
                                        @php $daysOverdue = now()->diffInDays($invoice->due_date, false) * -1; @endphp
                                        <span class="{{ $daysOverdue > 30 ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                            {{ max(0, $daysOverdue) }} days
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium">
                                        {{ Number::currency($invoice->total - $invoice->paid_amount, 'IDR') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($daysOverdue <= 30)
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Current</span>
                                        @elseif($daysOverdue <= 60)
                                            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Late</span>
                                        @else
                                            <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Overdue</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $arPaginator->links() }}
                </div>
            </div>
        </div>

        <!-- Trial Balance -->
        <div x-show="activeTab === 'trial_balance'" style="display: none;">
            @php $tbData = $this->getTrialBalanceData(); @endphp
            <x-filament::section>
                <div class="relative">
                    <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                        <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                            <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 uppercase">
                            <tr>
                                <th class="px-6 py-3 border-b">Account</th>
                                <th class="px-6 py-3 border-b text-right">Debit</th>
                                <th class="px-6 py-3 border-b text-right">Credit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Debits -->
                            @foreach($tbData['debits'] as $item)
                                <tr>
                                    <td class="px-6 py-3">{{ $item['name'] }}</td>
                                    <td class="px-6 py-3 text-right">{{ Number::currency($item['amount'], 'IDR') }}</td>
                                    <td class="px-6 py-3 text-right">-</td>
                                </tr>
                            @endforeach

                            <!-- Credits -->
                            @foreach($tbData['credits'] as $item)
                                <tr>
                                    <td class="px-6 py-3">{{ $item['name'] }}</td>
                                    <td class="px-6 py-3 text-right">-</td>
                                    <td class="px-6 py-3 text-right">{{ Number::currency($item['amount'], 'IDR') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 dark:bg-gray-800 font-bold">
                            <tr>
                                <td class="px-6 py-4">Total</td>
                                <td class="px-6 py-4 text-right {{ $tbData['total_debit'] != $tbData['total_credit'] ? 'text-red-600' : 'text-green-600' }}">
                                    {{ Number::currency($tbData['total_debit'], 'IDR') }}
                                </td>
                                <td class="px-6 py-4 text-right {{ $tbData['total_debit'] != $tbData['total_credit'] ? 'text-red-600' : 'text-green-600' }}">
                                    {{ Number::currency($tbData['total_credit'], 'IDR') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Assets Reports -->
        <div x-show="activeTab === 'assets'" style="display: none;">
            <!-- Date Filter for Asset Reports -->
            <x-filament::section class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Start Date</label>
                        <input type="date" wire:model.live="startDate" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-200">End Date</label>
                        <input type="date" wire:model.live="endDate" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
            </x-filament::section>

            @php 
                $damageData = $this->getDamagePenaltyData();
            @endphp
            <div class="space-y-6">
                
                <!-- 1. Asset Utilization -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Asset Utilization (Last 30 Days)</h3>
                        <div class="flex items-center gap-2">
                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button icon="heroicon-m-arrow-down-tray" color="gray">
                                        Export
                                    </x-filament::button>
                                </x-slot>

                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('utilization', 'pdf')" 
                                        icon="heroicon-m-document-text"
                                    >
                                        Export PDF
                                    </x-filament::dropdown.list.item>
                                    
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('utilization', 'csv')" 
                                        icon="heroicon-m-table-cells"
                                    >
                                        Export CSV
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <div class="w-64">
                                <label for="searchUtilization" class="sr-only">Search</label>
                                <input 
                                    type="text" 
                                    id="searchUtilization" 
                                    wire:model.live.debounce.500ms="searchUtilization" 
                                    class="block w-full py-2 px-3 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                                    placeholder="Search..."
                                >
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Asset Name</th>
                                    <th class="px-6 py-3">Days Rented</th>
                                    <th class="px-6 py-3">Utilization Rate</th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getUtilizationAssets() as $unit)
                                    @php $item = $this->getAssetMetrics($unit)['utilization']; @endphp
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $item['name'] }}</td>
                                        <td class="px-6 py-4">{{ $item['days_rented'] }} days</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <span class="mr-2">{{ $item['utilization_rate'] }}%</span>
                                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 max-w-[100px]">
                                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $item['utilization_rate'] }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-lg {{ $item['status'] === 'High' ? 'bg-green-100 text-green-800' : ($item['status'] === 'Low' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ $item['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $this->getUtilizationAssets()->links(data: ['scrollTo' => false]) }}
                        </div>
                    </div>
                    </div>
                </x-filament::section>

                <!-- 2. Revenue per Asset -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Revenue per Asset (Lifetime)</h3>
                        <div class="flex items-center gap-2">
                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button icon="heroicon-m-arrow-down-tray" color="gray">
                                        Export
                                    </x-filament::button>
                                </x-slot>

                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('revenue', 'pdf')" 
                                        icon="heroicon-m-document-text"
                                    >
                                        Export PDF
                                    </x-filament::dropdown.list.item>
                                    
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('revenue', 'csv')" 
                                        icon="heroicon-m-table-cells"
                                    >
                                        Export CSV
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <div class="w-64">
                                <label for="searchRevenue" class="sr-only">Search</label>
                                <input 
                                    type="text" 
                                    id="searchRevenue" 
                                    wire:model.live.debounce.500ms="searchRevenue" 
                                    class="block w-full py-2 px-3 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                                    placeholder="Search..."
                                >
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Asset Name</th>
                                    <th class="px-6 py-3">Total Revenue</th>
                                    <th class="px-6 py-3">ROI (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getRevenueAssets() as $unit)
                                    @php $item = $this->getAssetMetrics($unit)['revenue']; @endphp
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $item['name'] }}</td>
                                        <td class="px-6 py-4">{{ Number::currency($item['revenue'], 'IDR') }}</td>
                                        <td class="px-6 py-4 {{ $item['roi'] > 100 ? 'text-green-600' : 'text-gray-600' }} font-bold">{{ $item['roi'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $this->getRevenueAssets()->links(data: ['scrollTo' => false]) }}
                        </div>
                    </div>
                    </div>
                </x-filament::section>

                <!-- 3. Damage & Penalty Report -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <h3 class="text-lg font-medium mb-4">Damage & Penalty Report (Global)</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Type</th>
                                    <th class="px-6 py-3">Description</th>
                                    <th class="px-6 py-3">Amount Collected</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($damageData as $item)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $item['type'] }}</td>
                                        <td class="px-6 py-4">{{ $item['description'] }}</td>
                                        <td class="px-6 py-4 font-bold text-green-600">{{ Number::currency($item['amount'], 'IDR') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </x-filament::section>

                <!-- 4. Maintenance Report -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Maintenance Report</h3>
                        <div class="flex items-center gap-2">
                            <!-- Frequency Filter -->
                            <select wire:model.live="maintenanceFrequencyFilter" class="block w-40 py-2 px-3 border border-gray-300 rounded-md leading-5 bg-white focus:outline-none focus:placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="all">All Frequencies</option>
                                <option value="high">High (>3)</option>
                                <option value="low">Low (1-3)</option>
                            </select>

                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button icon="heroicon-m-arrow-down-tray" color="gray">
                                        Export
                                    </x-filament::button>
                                </x-slot>

                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('maintenance', 'pdf')" 
                                        icon="heroicon-m-document-text"
                                    >
                                        Export PDF
                                    </x-filament::dropdown.list.item>
                                    
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('maintenance', 'csv')" 
                                        icon="heroicon-m-table-cells"
                                    >
                                        Export CSV
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <div class="w-64">
                                <label for="searchMaintenance" class="sr-only">Search</label>
                                <input 
                                    type="text" 
                                    id="searchMaintenance" 
                                    wire:model.live.debounce.500ms="searchMaintenance" 
                                    class="block w-full py-2 px-3 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                                    placeholder="Search..."
                                >
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Asset Name</th>
                                    <th class="px-6 py-3">Total Cost</th>
                                    <th class="px-6 py-3">Frequency</th>
                                    <th class="px-6 py-3">Last Maintenance</th>
                                    <th class="px-6 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getMaintenanceAssets() as $unit)
                                    @php $item = $this->getAssetMetrics($unit)['maintenance']; @endphp
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $item['name'] }}</td>
                                        <td class="px-6 py-4">{{ Number::currency($item['total_cost'], 'IDR') }}</td>
                                        <td class="px-6 py-4">{{ $item['frequency'] }}</td>
                                        <td class="px-6 py-4">{{ $item['last_maintenance'] ? $item['last_maintenance']->format('d M Y') : '-' }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-medium rounded-lg {{ $item['status'] === 'Efficient' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $item['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $this->getMaintenanceAssets()->links(data: ['scrollTo' => false]) }}
                        </div>
                    </div>
                    </div>
                </x-filament::section>

                <!-- 5. Depreciation Report -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Depreciation Report</h3>
                        <div class="flex items-center gap-2">
                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button icon="heroicon-m-arrow-down-tray" color="gray">
                                        Export
                                    </x-filament::button>
                                </x-slot>

                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('depreciation', 'pdf')" 
                                        icon="heroicon-m-document-text"
                                    >
                                        Export PDF
                                    </x-filament::dropdown.list.item>
                                    
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('depreciation', 'csv')" 
                                        icon="heroicon-m-table-cells"
                                    >
                                        Export CSV
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>

                            <div class="w-64">
                                <label for="searchDepreciation" class="sr-only">Search</label>
                                <input 
                                    type="text" 
                                    id="searchDepreciation" 
                                    wire:model.live.debounce.500ms="searchDepreciation" 
                                    class="block w-full py-2 px-3 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                                    placeholder="Search..."
                                >
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Asset Name</th>
                                    <th class="px-6 py-3">Purchase Price</th>
                                    <th class="px-6 py-3">Monthly Depr.</th>
                                    <th class="px-6 py-3">Accumulated Depr.</th>
                                    <th class="px-6 py-3">Book Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getDepreciationAssets() as $unit)
                                    @php $item = $this->getAssetMetrics($unit)['depreciation']; @endphp
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $item['name'] }}</td>
                                        <td class="px-6 py-4">{{ Number::currency($item['purchase_price'], 'IDR') }}</td>
                                        <td class="px-6 py-4">{{ Number::currency($item['monthly_depreciation'], 'IDR') }}</td>
                                        <td class="px-6 py-4">{{ Number::currency($item['accumulated_depreciation'], 'IDR') }}</td>
                                        <td class="px-6 py-4 font-bold">{{ Number::currency($item['book_value'], 'IDR') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $this->getDepreciationAssets()->links(data: ['scrollTo' => false]) }}
                        </div>
                    </div>
                    </div>
                </x-filament::section>

                <!-- 6. Lost & Damaged Asset Report -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Lost & Damaged Asset Report</h3>
                        <div class="flex items-center gap-2">
                            <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button icon="heroicon-m-arrow-down-tray" color="gray">
                                        Export
                                    </x-filament::button>
                                </x-slot>
                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('lost_damaged', 'pdf')" 
                                        icon="heroicon-m-document-text"
                                    >
                                        Export PDF
                                    </x-filament::dropdown.list.item>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('lost_damaged', 'csv')" 
                                        icon="heroicon-m-table-cells"
                                    >
                                        Export CSV
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                            <div class="w-64">
                                <label for="searchLostDamaged" class="sr-only">Search</label>
                                <input 
                                    type="text" 
                                    id="searchLostDamaged" 
                                    wire:model.live.debounce.500ms="searchLostDamaged" 
                                    class="block w-full py-2 px-3 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                                    placeholder="Search by asset name or serial number..."
                                >
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Asset Name (Serial Number)</th>
                                    <th class="px-6 py-3">Condition</th>
                                    <th class="px-6 py-3">Date Reported</th>
                                    <th class="px-6 py-3">Purchase Price</th>
                                    <th class="px-6 py-3">Accumulated Depreciation</th>
                                    <th class="px-6 py-3">Book Value (Loss Amount)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getLostDamagedAssets() as $unit)
                                    @php $metrics = $this->getAssetMetrics($unit); $lostDamaged = $metrics['lost_damaged']; @endphp
                                    @if(!empty($lostDamaged))
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ $lostDamaged['name'] }}</td>
                                        <td class="px-6 py-4 {{ $lostDamaged['condition'] === 'Lost' ? 'text-red-600' : 'text-orange-600' }} font-bold">{{ $lostDamaged['condition'] }}</td>
                                        <td class="px-6 py-4">{{ $lostDamaged['date_reported']->format('d M Y') }}</td>
                                        <td class="px-6 py-4">{{ Number::currency($lostDamaged['purchase_price'], 'IDR') }}</td>
                                        <td class="px-6 py-4">{{ Number::currency($lostDamaged['accumulated_depreciation'], 'IDR') }}</td>
                                        <td class="px-6 py-4 text-red-600 dark:text-red-400 font-bold">{{ Number::currency($lostDamaged['book_value'], 'IDR') }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $this->getLostDamagedAssets()->links(data: ['scrollTo' => false]) }}
                        </div>
                    </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Customer Reports -->
        <div x-show="activeTab === 'customers'" style="display: none;">
            <div class="space-y-6">
                <!-- Top Customers Section -->
                <x-filament::section>
                    <div class="relative">
                        <div wire:loading.delay wire:target="searchCustomer, getTopCustomers" class="absolute inset-0 z-50 bg-white/50 dark:bg-gray-900/50 backdrop-blur-[2px] rounded-xl">
                            <div class="sticky top-0 h-screen max-h-full flex items-center justify-center">
                                <x-filament::loading-indicator class="h-12 w-12 text-primary-600 dark:text-primary-400" />
                            </div>
                        </div>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Top Customers (VIP)</h3>
                        <div class="flex items-center gap-2">
                             <x-filament::dropdown>
                                <x-slot name="trigger">
                                    <x-filament::button icon="heroicon-m-arrow-down-tray" color="gray">
                                        Export
                                    </x-filament::button>
                                </x-slot>
                                <x-filament::dropdown.list>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('top_customers', 'pdf')" 
                                        icon="heroicon-m-document-text"
                                    >
                                        Export PDF
                                    </x-filament::dropdown.list.item>
                                    <x-filament::dropdown.list.item 
                                        wire:click="exportReport('top_customers', 'csv')" 
                                        icon="heroicon-m-table-cells"
                                    >
                                        Export CSV
                                    </x-filament::dropdown.list.item>
                                </x-filament::dropdown.list>
                            </x-filament::dropdown>
                             <div class="w-64">
                                <label for="searchCustomer" class="sr-only">Search</label>
                                <input 
                                    type="text" 
                                    id="searchCustomer" 
                                    wire:model.live.debounce.500ms="searchCustomer" 
                                    class="block w-full py-2 px-3 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" 
                                    placeholder="Search customer..."
                                >
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto border rounded-lg dark:border-gray-700">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Rank</th>
                                    <th scope="col" class="px-6 py-3">Customer Name</th>
                                    <th scope="col" class="px-6 py-3 text-center">Total Bookings</th>
                                    <th scope="col" class="px-6 py-3 text-right">Total Revenue</th>
                                    <th scope="col" class="px-6 py-3 text-right">Avg. Transaction</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $topCustomers = $this->getTopCustomers(); 
                                    $rankStart = ($topCustomers->currentPage() - 1) * $topCustomers->perPage() + 1;
                                @endphp
                                @forelse($topCustomers as $index => $customer)
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                            #{{ $rankStart + $index }}
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                            <div class="flex flex-col">
                                                <span>{{ $customer->name }}</span>
                                                <span class="text-xs text-gray-500">{{ $customer->email }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            {{ $customer->invoices_count }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">
                                            {{ Number::currency($customer->invoices_sum_total ?? 0, 'IDR') }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @php
                                                $avg = ($customer->invoices_count > 0) ? (($customer->invoices_sum_total ?? 0) / $customer->invoices_count) : 0;
                                            @endphp
                                            {{ Number::currency($avg, 'IDR') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No customers found with revenue data.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $topCustomers->links() }}
                    </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <!-- Tax Reports -->
        <div x-show="activeTab === 'tax'" style="display: none;">
            @php $taxData = $this->getTaxSummary(); @endphp
            <div class="space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Output VAT -->
                    <x-filament::section>
                        <div class="flex flex-col">
                            <span class="text-gray-500 dark:text-gray-400 text-sm">Output VAT (PPN Keluaran)</span>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ Number::currency($taxData['output_vat'], 'IDR') }}</span>
                        </div>
                    </x-filament::section>
                    
                    <!-- Input VAT -->
                    <x-filament::section>
                        <div class="flex flex-col">
                            <span class="text-gray-500 dark:text-gray-400 text-sm">Input VAT (PPN Masukan)</span>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ Number::currency($taxData['input_vat'], 'IDR') }}</span>
                        </div>
                    </x-filament::section>
                    
                    <!-- Net VAT -->
                    <x-filament::section>
                        <div class="flex flex-col">
                            <span class="text-gray-500 dark:text-gray-400 text-sm">Net VAT (Kurang/Lebih Bayar)</span>
                            <span class="text-2xl font-bold {{ $taxData['net_vat'] >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ Number::currency($taxData['net_vat'], 'IDR') }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $taxData['net_vat'] >= 0 ? 'Payable (Kurang Bayar)' : 'Refundable (Lebih Bayar)' }}</span>
                        </div>
                    </x-filament::section>
                    
                    <!-- PPh -->
                    <x-filament::section>
                        <div class="flex flex-col">
                            <span class="text-gray-500 dark:text-gray-400 text-sm">Total PPh 23 (Withheld)</span>
                            <span class="text-2xl font-bold text-blue-600">{{ Number::currency($taxData['pph'], 'IDR') }}</span>
                        </div>
                    </x-filament::section>
                </div>
                
                <!-- Output VAT Table -->
                <x-filament::section>
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium">Output VAT (Invoices)</h3>
                        <div class="w-64">
                             <input type="text" wire:model.live.debounce.500ms="searchTax" placeholder="Search invoice..." class="block w-full py-2 px-3 border border-gray-300 rounded-md bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm">
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left dark:text-gray-400">
                            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Invoice #</th>
                                    <th class="px-6 py-3">Customer</th>
                                    <th class="px-6 py-3">Tax Invoice #</th>
                                    <th class="px-6 py-3 text-right">DPP (Tax Base)</th>
                                    <th class="px-6 py-3 text-right">PPN (VAT)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getOutputVatItems() as $invoice)
                                    <tr class="bg-white border-b dark:bg-gray-800">
                                        <td class="px-6 py-4">{{ $invoice->date->format('d M Y') }}</td>
                                        <td class="px-6 py-4">{{ $invoice->number }}</td>
                                        <td class="px-6 py-4">{{ $invoice->user->name ?? '-' }}</td>
                                        <td class="px-6 py-4">{{ $invoice->tax_invoice_number ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right">{{ Number::currency($invoice->tax_base, 'IDR') }}</td>
                                        <td class="px-6 py-4 text-right">{{ Number::currency($invoice->ppn_amount, 'IDR') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">{{ $this->getOutputVatItems()->links() }}</div>
                    </div>
                </x-filament::section>
                
                <!-- Input VAT Table -->
                <x-filament::section>
                     <div class="mb-4">
                        <h3 class="text-lg font-medium">Input VAT (Bills/Expenses)</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left dark:text-gray-400">
                            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3">Date</th>
                                    <th class="px-6 py-3">Bill #</th>
                                    <th class="px-6 py-3">Vendor</th>
                                    <th class="px-6 py-3">Tax Invoice #</th>
                                    <th class="px-6 py-3 text-right">Total Amount</th>
                                    <th class="px-6 py-3 text-right">VAT Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($this->getInputVatItems() as $bill)
                                    <tr class="bg-white border-b dark:bg-gray-800">
                                        <td class="px-6 py-4">{{ $bill->bill_date ? $bill->bill_date->format('d M Y') : '-' }}</td>
                                        <td class="px-6 py-4">{{ $bill->bill_number }}</td>
                                        <td class="px-6 py-4">{{ $bill->vendor_name }}</td>
                                        <td class="px-6 py-4">{{ $bill->tax_invoice_number ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right">{{ Number::currency($bill->amount, 'IDR') }}</td>
                                        <td class="px-6 py-4 text-right">{{ Number::currency($bill->tax_amount, 'IDR') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">{{ $this->getInputVatItems()->links() }}</div>
                    </div>
                </x-filament::section>
            </div>
        </div>

    </div>
</x-filament-panels::page>
