<?php

namespace App\Filament\Clusters\Finance\Pages;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Models\Bill;
use App\Models\Expense;
use App\Models\FinanceAccount;
use App\Models\FinanceTransaction;
use App\Models\Invoice;
use App\Models\ProductUnit;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\User;
use App\Models\MaintenanceRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Livewire\WithPagination;
use Livewire\WithoutScrolling;
use Livewire\Attributes\Url;
use BackedEnum;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialReports extends Page
{
    use WithPagination;

    protected static ?string $cluster = FinanceCluster::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Reports';
    
    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.clusters.finance.pages.financial-reports';
#[Url]
    public $activeTab = 'profit_loss';

    #[Url]
    public $searchUtilization = '';

    #[Url]
    public $searchRevenue = '';

    #[Url]
    public $searchMaintenance = '';

    #[Url]
    public $searchDepreciation = '';

    #[Url]
    public $searchLostDamaged = '';

    #[Url]
    public $searchCustomer = '';

    #[Url]
    public $searchTax = '';

    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    #[Url]
    public $maintenanceFrequencyFilter = 'all'; // all, high, low

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
    }

    public function getView(): string
    {
        return 'filament.pages.financial-reports';
    }

    public function getProfitLossData(): array
    {
        // Revenue: Invoices + Direct Income (not linked to invoice)
        $invoiceRevenue = Invoice::sum('total');
        $directIncome = FinanceTransaction::where('type', FinanceTransaction::TYPE_INCOME)
            ->whereNull('reference_type')
            ->sum('amount');
            
        $totalRevenue = $invoiceRevenue + $directIncome;

        // Expenses: Bills + Direct Expenses (not linked to bill)
        $billExpenses = Bill::sum('amount');
        $directExpenses = Expense::sum('amount'); // Using Expense model scope
        
        // Add Lost/Damaged Asset Write-offs
        $assetWriteOffs = $this->calculateLostDamagedLoss();
        
        $totalExpenses = $billExpenses + $directExpenses + $assetWriteOffs;
        
        return [
            'income' => $totalRevenue,
            'expenses' => $totalExpenses,
            'net_profit' => $totalRevenue - $totalExpenses,
        ];
    }

    protected function calculateLostDamagedLoss(): float
    {
        $lostUnits = ProductUnit::whereIn('condition', ['lost', 'broken'])
            ->where('status', ProductUnit::STATUS_RETIRED)
            ->get();
            
        $totalLoss = 0;
        
        foreach ($lostUnits as $unit) {
            $purchasePrice = $unit->purchase_price ?? 0;
            $residualValue = $unit->residual_value ?? 0;
            $usefulLifeMonths = $unit->useful_life ?? 60;
            
            $monthlyDepreciation = ($usefulLifeMonths > 0) ? ($purchasePrice - $residualValue) / $usefulLifeMonths : 0;
            
            $purchaseDate = $unit->purchase_date;
            $retirementDate = $unit->updated_at; // updated_at is retirement date
            
            $ageAtRetirement = ($purchaseDate && $retirementDate) ? $purchaseDate->diffInMonths($retirementDate) : 0;
            $accDeprAtRetirement = min($purchasePrice - $residualValue, $monthlyDepreciation * $ageAtRetirement);
            $bookValueAtRetirement = max($residualValue, $purchasePrice - $accDeprAtRetirement);
            
            $totalLoss += $bookValueAtRetirement;
        }
        
        return round($totalLoss, 2);
    }

    public function getDetailedProfitLossData(): array
    {
        // 1. REVENUE
        $revenueItems = [];
        
        // Rental Revenue (from Invoices)
        $rentalRevenue = Invoice::sum('total');
        if ($rentalRevenue > 0) {
            $revenueItems[] = ['name' => 'Rental Revenue', 'amount' => $rentalRevenue];
        }

        // Other Income (Direct Transactions) grouped by category
        $otherIncomes = FinanceTransaction::where('type', FinanceTransaction::TYPE_INCOME)
            ->whereNull('reference_type')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        foreach ($otherIncomes as $category => $amount) {
            $revenueItems[] = ['name' => $category ?: 'Other Income', 'amount' => $amount];
        }

        $totalRevenue = collect($revenueItems)->sum('amount');


        // 2. COGS (Cost of Revenue)
        $cogsItems = [];

        // Maintenance Costs
        $maintenanceCost = MaintenanceRecord::sum('cost');
        if ($maintenanceCost > 0) {
            $cogsItems[] = ['name' => 'Maintenance & Repairs', 'amount' => $maintenanceCost];
        }

        // Depreciation (treated as COGS or Expense? Usually Expense, but for rental assets it can be COGS)
        // Let's put it in Expenses for standard P&L, or COGS if we want "Gross Profit" from rentals.
        // Standard Odoo example puts "HPP" (COGS). For rental, depreciation of rental assets is often COGS.
        // Let's calculate total accumulated depreciation for all time.
        $totalDepreciation = ProductUnit::all()->reduce(function ($carry, $unit) {
            $purchasePrice = $unit->purchase_price ?? 0;
            $residualValue = $unit->residual_value ?? 0;
            $usefulLifeMonths = $unit->useful_life ?? 60;
            
            $monthlyDepreciation = ($usefulLifeMonths > 0) ? ($purchasePrice - $residualValue) / $usefulLifeMonths : 0;
            
            $purchaseDate = $unit->purchase_date;
            // If retired, stop depreciation at retirement date (updated_at)
            $endDate = ($unit->status === ProductUnit::STATUS_RETIRED) ? $unit->updated_at : now();
            
            $ageMonths = ($purchaseDate && $endDate) ? $purchaseDate->diffInMonths($endDate) : 0;
            $accumulatedDepreciation = min($purchasePrice - $residualValue, $monthlyDepreciation * $ageMonths);
            
            return $carry + $accumulatedDepreciation;
        }, 0);

        if ($totalDepreciation > 0) {
            $cogsItems[] = ['name' => 'Asset Depreciation', 'amount' => $totalDepreciation];
        }

        $totalCOGS = collect($cogsItems)->sum('amount');
        $grossProfit = $totalRevenue - $totalCOGS;


        // 3. OPERATING EXPENSES
        $expenseItems = [];

        // Bills grouped by category
        $billExpenses = Bill::selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        foreach ($billExpenses as $category => $amount) {
            $expenseItems[] = ['name' => $category ?: 'Uncategorized Bills', 'amount' => $amount];
        }

        // Direct Expenses grouped by category
        $directExpenses = Expense::selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        foreach ($directExpenses as $category => $amount) {
            // Check if this category already exists from Bills, if so merge
            $existingIndex = null;
            foreach ($expenseItems as $index => $item) {
                if ($item['name'] === ($category ?: 'Uncategorized Expenses')) {
                    $existingIndex = $index;
                    break;
                }
            }

            if ($existingIndex !== null) {
                $expenseItems[$existingIndex]['amount'] += $amount;
            } else {
                $expenseItems[] = ['name' => $category ?: 'Uncategorized Expenses', 'amount' => $amount];
            }
        }
        
        // Add Lost/Damaged Asset Write-offs
        $assetWriteOffs = $this->calculateLostDamagedLoss();
        if ($assetWriteOffs > 0) {
            $expenseItems[] = ['name' => 'Asset Write-offs (Lost/Damaged)', 'amount' => $assetWriteOffs];
        }

        $totalExpenses = collect($expenseItems)->sum('amount');

        // 4. NET PROFIT
        // Net Profit = Gross Profit - Operating Expenses
        $netProfit = $grossProfit - $totalExpenses;

        return [
            'revenue' => [
                'items' => $revenueItems,
                'total' => $totalRevenue,
            ],
            'cogs' => [
                'items' => $cogsItems,
                'total' => $totalCOGS,
            ],
            'gross_profit' => $grossProfit,
            'expenses' => [
                'items' => $expenseItems,
                'total' => $totalExpenses,
            ],
            'net_profit' => $netProfit,
        ];
    }

    public function getBalanceSheetData(): array
    {
        $currentAssets = FinanceAccount::sum('balance'); // Cash & Bank
        $accountsReceivable = Invoice::where('status', '!=', Invoice::STATUS_PAID)
            ->get()
            ->sum(fn($inv) => $inv->total - $inv->paid_amount);
            
        $fixedAssets = ProductUnit::where('status', '!=', ProductUnit::STATUS_RETIRED)
            ->get()
            ->sum(fn($unit) => $unit->current_value);
        
        $totalAssets = $currentAssets + $accountsReceivable + $fixedAssets;

        $accountsPayable = Bill::where('status', '!=', Bill::STATUS_PAID)
            ->get()
            ->sum(fn($bill) => $bill->amount - $bill->paid_amount);
            
        // Simplified Equity Calculation
        $equity = $totalAssets - $accountsPayable;

        return [
            'assets' => [
                'Cash & Bank' => $currentAssets,
                'Accounts Receivable' => $accountsReceivable,
                'Fixed Assets (Equipment)' => $fixedAssets,
            ],
            'total_assets' => $totalAssets,
            'liabilities' => [
                'Accounts Payable' => $accountsPayable,
            ],
            'total_liabilities' => $accountsPayable,
            'equity' => $equity,
        ];
    }

    public function getCashFlowData(): array
    {
        $inflow = FinanceTransaction::whereIn('type', [FinanceTransaction::TYPE_INCOME, FinanceTransaction::TYPE_DEPOSIT_IN])->sum('amount');
        $outflow = FinanceTransaction::whereIn('type', [FinanceTransaction::TYPE_EXPENSE, FinanceTransaction::TYPE_DEPOSIT_OUT])->sum('amount');

        return [
            'operating_activities' => [
                'Inflow' => $inflow,
                'Outflow' => $outflow,
            ],
            'net_cash_flow' => $inflow - $outflow,
        ];
    }

    protected function getFilteredAssetsQuery(string $search)
    {
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();

        $query = ProductUnit::with(['product', 'rentalItems' => function($query) use ($startDate, $endDate) {
            $query->select('product_unit_id', 'days', 'subtotal', 'created_at')
                  ->whereBetween('created_at', [$startDate, $endDate]);
        }, 'maintenanceRecords' => function($query) use ($startDate, $endDate) {
            $query->select('product_unit_id', 'cost', 'date')
                  ->whereBetween('date', [$startDate, $endDate]);
        }]);

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('serial_number', 'like', '%' . $search . '%')
                  ->orWhereHas('product', function($sq) use ($search) {
                      $sq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }
        
        return $query;
    }

    protected function getFilteredAssets(string $search, string $pageName)
    {
        return $this->getFilteredAssetsQuery($search)->paginate(10, ['*'], $pageName);
    }

    public function exportReport(string $type, string $format)
    {
        if ($type === 'profit_loss') {
            return $this->exportProfitLoss($format);
        }

        if ($type === 'top_customers') {
            return $this->exportTopCustomers($format);
        }

        if ($type === 'tax_output') {
             return $this->exportTaxOutput($format);
        }

        if ($type === 'tax_input') {
             return $this->exportTaxInput($format);
        }

        // Get all records matching current search (not paginated)
        $search = $this->{'search' . str_replace('_', '', ucwords($type, '_'))} ?? '';
        
        if ($type === 'lost_damaged') {
             $query = ProductUnit::with(['product', 'maintenanceRecords'])
                ->whereIn('condition', ['lost', 'broken'])
                ->where('status', ProductUnit::STATUS_RETIRED);
                
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('serial_number', 'like', '%' . $search . '%')
                      ->orWhereHas('product', function($sq) use ($search) {
                          $sq->where('name', 'like', '%' . $search . '%');
                      });
                });
            }
        } else {
            $query = $this->getFilteredAssetsQuery($search);
        }
        
        $records = $query->get();

        $data = $records->map(function ($unit) {
            return $this->getAssetMetrics($unit);
        });

        $filename = 'asset-' . $type . '-report-' . now()->format('Y-m-d');

        if ($format === 'csv') {
            return $this->exportCsv($data, $type, $filename);
        } elseif ($format === 'pdf') {
            return $this->exportPdf($data, $type, $filename);
        }
    }

    protected function exportProfitLoss(string $format)
    {
        $plData = $this->getDetailedProfitLossData();
        $filename = 'profit-loss-report-' . now()->format('Y-m-d');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($plData) {
                $file = fopen('php://output', 'w');
                
                // Add BOM for Excel compatibility
                fputs($file, "\xEF\xBB\xBF");
                
                fputcsv($file, ['Category', 'Description', 'Amount']);

                // Revenue
                fputcsv($file, ['REVENUE', '', '']);
                foreach ($plData['revenue']['items'] as $item) {
                    fputcsv($file, ['', $item['name'], $item['amount']]);
                }
                fputcsv($file, ['', 'Total Revenue', $plData['revenue']['total']]);

                // COGS
                fputcsv($file, ['COST OF REVENUE', '', '']);
                foreach ($plData['cogs']['items'] as $item) {
                    fputcsv($file, ['', $item['name'], $item['amount']]);
                }
                fputcsv($file, ['', 'Total COGS', $plData['cogs']['total']]);
                fputcsv($file, ['', 'GROSS PROFIT', $plData['gross_profit']]);

                // Expenses
                fputcsv($file, ['OPERATING EXPENSES', '', '']);
                foreach ($plData['expenses']['items'] as $item) {
                    fputcsv($file, ['', $item['name'], $item['amount']]);
                }
                fputcsv($file, ['', 'Total Expenses', $plData['expenses']['total']]);

                // Net Profit
                fputcsv($file, ['', 'NET PROFIT', $plData['net_profit']]);

                fclose($file);
            }, $filename . '.csv');
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('filament.pages.reports.profit-loss-pdf', [
                'plData' => $plData,
                'title' => 'Profit & Loss Statement',
                'date' => now()->format('d M Y')
            ]);
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename . '.pdf');
        }
    }

    protected function exportCsv($data, $type, $filename)
    {
        $headers = match($type) {
            'utilization' => ['Asset Name', 'Days Rented', 'Utilization Rate (%)', 'Status'],
            'revenue' => ['Asset Name', 'Total Revenue', 'ROI (%)'],
            'maintenance' => ['Asset Name', 'Total Cost', 'Frequency', 'Last Maintenance', 'Status'],
            'depreciation' => ['Asset Name', 'Purchase Price', 'Monthly Depr.', 'Accumulated Depr.', 'Book Value'],
            'lost_damaged' => ['Asset Name', 'Condition', 'Date Reported', 'Purchase Price', 'Accumulated Depr.', 'Book Value (Loss)'],
            default => []
        };

        return response()->streamDownload(function () use ($data, $type, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($data as $item) {
                $metric = $item[$type];
                
                $row = match($type) {
                    'utilization' => [
                        $metric['name'], 
                        $metric['days_rented'], 
                        $metric['utilization_rate'], 
                        $metric['status']
                    ],
                    'revenue' => [
                        $metric['name'], 
                        $metric['revenue'], 
                        $metric['roi']
                    ],
                    'maintenance' => [
                        $metric['name'], 
                        $metric['total_cost'], 
                        $metric['frequency'], 
                        $metric['last_maintenance'] ? $metric['last_maintenance']->format('Y-m-d') : '-',
                        $metric['status']
                    ],
                    'depreciation' => [
                        $metric['name'], 
                        $metric['purchase_price'], 
                        $metric['monthly_depreciation'], 
                        $metric['accumulated_depreciation'], 
                        $metric['book_value']
                    ],
                    'lost_damaged' => [
                        $metric['name'],
                        $metric['condition'],
                        $metric['date_reported'] ? $metric['date_reported']->format('Y-m-d') : '-',
                        $metric['purchase_price'],
                        $metric['accumulated_depreciation'],
                        $metric['book_value']
                    ],
                    default => []
                };
                
                fputcsv($file, $row);
            }
            fclose($file);
        }, $filename . '.csv');
    }

    protected function exportPdf($data, $type, $filename)
    {
        $view = 'filament.pages.reports.asset-pdf';
        
        $pdf = Pdf::loadView($view, [
            'data' => $data,
            'type' => $type,
            'title' => ucfirst($type) . ' Report',
            'date' => now()->format('d M Y')
        ]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename . '.pdf');
    }


    public function updated($property)
    {
        if ($property === 'searchUtilization') {
            $this->resetPage('utilization_page');
        }

        if ($property === 'searchRevenue') {
            $this->resetPage('revenue_page');
        }

        if ($property === 'searchMaintenance') {
            $this->resetPage('maintenance_page');
        }

        if ($property === 'searchDepreciation') {
            $this->resetPage('depreciation_page');
        }

        if ($property === 'searchLostDamaged') {
            $this->resetPage('lost_damaged_page');
        }

        if ($property === 'searchCustomer') {
            $this->resetPage('customer_page');
        }

        if ($property === 'searchTax') {
            $this->resetPage('tax_out_page');
        }
    }

    public function getUtilizationAssets()
    {
        return $this->getFilteredAssets($this->searchUtilization, 'utilization_page');
    }

    public function getRevenueAssets()
    {
        return $this->getFilteredAssets($this->searchRevenue, 'revenue_page');
    }

    public function getMaintenanceAssets()
    {
        $query = $this->getFilteredAssetsQuery($this->searchMaintenance);
        
        if ($this->maintenanceFrequencyFilter !== 'all') {
            $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
            $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();
            
            $callback = function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            };
            
            if ($this->maintenanceFrequencyFilter === 'high') {
                // More than 3 records in the period
                $query->whereHas('maintenanceRecords', $callback, '>', 3);
            } elseif ($this->maintenanceFrequencyFilter === 'low') {
                // 1-3 records
                $query->whereHas('maintenanceRecords', $callback, '>=', 1)
                      ->whereHas('maintenanceRecords', $callback, '<=', 3);
            }
        }

        return $query->paginate(10, ['*'], 'maintenance_page');
    }

    public function getDepreciationAssets()
    {
        return $this->getFilteredAssets($this->searchDepreciation, 'depreciation_page');
    }

    public function getLostDamagedAssets()
    {
        $search = $this->searchLostDamaged;
        
        $query = ProductUnit::with(['product', 'maintenanceRecords'])
            ->whereIn('condition', ['lost', 'broken'])
            ->where('status', ProductUnit::STATUS_RETIRED); // Only show fully retired/written-off assets
            
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('serial_number', 'like', '%' . $search . '%')
                  ->orWhereHas('product', function($sq) use ($search) {
                      $sq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }
        
        return $query->paginate(10, ['*'], 'lost_damaged_page');
    }

    public function getTopCustomers()
    {
        return $this->getTopCustomersQuery()->paginate(10, ['*'], 'customer_page');
    }

    protected function exportTopCustomers(string $format)
    {
        $customers = $this->getTopCustomersQuery()->get();
        $filename = 'top-customers-report-' . now()->format('Y-m-d');
        
        if ($format === 'csv') {
            return response()->streamDownload(function () use ($customers) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Rank', 'Customer Name', 'Email', 'Total Bookings', 'Total Revenue', 'Avg. Transaction Value']);

                foreach ($customers as $index => $customer) {
                    $totalRevenue = $customer->invoices_sum_total ?? 0;
                    $invoiceCount = $customer->invoices_count ?? 0;
                    $avgValue = $invoiceCount > 0 ? $totalRevenue / $invoiceCount : 0;
                    
                    fputcsv($file, [
                        $index + 1,
                        $customer->name,
                        $customer->email,
                        $invoiceCount,
                        $totalRevenue,
                        number_format($avgValue, 2, '.', '')
                    ]);
                }
                fclose($file);
            }, $filename . '.csv');
        } elseif ($format === 'pdf') {
            $pdf = Pdf::loadView('filament.pages.reports.top-customers-pdf', [
                'customers' => $customers,
                'title' => 'Top Customers Report',
                'date' => now()->format('d M Y')
            ]);
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename . '.pdf');
        }
    }

    protected function getTopCustomersQuery()
    {
        $query = User::query()
            ->whereHas('invoices', function ($q) {
                $q->whereIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_WAITING_FOR_PAYMENT]);
            })
            ->withSum(['invoices' => function ($q) {
                $q->whereIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_WAITING_FOR_PAYMENT]);
            }], 'total')
            ->withCount(['invoices' => function ($q) {
                $q->whereIn('status', [Invoice::STATUS_PAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_WAITING_FOR_PAYMENT]);
            }])
            ->orderByDesc('invoices_sum_total');

        if ($this->searchCustomer) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->searchCustomer . '%')
                  ->orWhere('email', 'like', '%' . $this->searchCustomer . '%');
            });
        }
        
        return $query;
    }


    public function getAssetMetrics(ProductUnit $unit): array
    {
        $productName = ($unit->product->name ?? 'Unknown') . ' (' . $unit->serial_number . ')';
        
        // 1. Asset Utilization (Selected Period)
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();
        $daysInPeriod = max(1, $startDate->diffInDays($endDate) + 1);

        // Sum days from rentals created within the period (eager loaded)
        $daysRentedInPeriod = $unit->rentalItems->sum('days');
        
        $utilizationRate = min(100, round(($daysRentedInPeriod / $daysInPeriod) * 100, 1));
        
        $utilization = [
            'name' => $productName,
            'days_rented' => $daysRentedInPeriod,
            'utilization_rate' => $utilizationRate,
            'status' => $utilizationRate > 70 ? 'High' : ($utilizationRate < 30 ? 'Low' : 'Medium'),
        ];

        // 2. Revenue per Asset (Lifetime)
        $totalRevenue = $unit->rentalItems->sum('subtotal');
        $revenue = [
            'name' => $productName,
            'revenue' => $totalRevenue,
            'roi' => ($unit->purchase_price > 0) ? round(($totalRevenue / $unit->purchase_price) * 100, 1) : 0,
        ];

        // 3. Maintenance Report
        $totalMaintenanceCost = $unit->maintenanceRecords->sum('cost');
        $maintenance = [
            'name' => $productName,
            'total_cost' => $totalMaintenanceCost,
            'frequency' => $unit->maintenanceRecords->count(),
            'last_maintenance' => $unit->maintenanceRecords->max('date'),
            'status' => ($totalMaintenanceCost > $totalRevenue) ? 'Inefficient' : 'Efficient',
        ];

        // 4. Depreciation Report
        $purchasePrice = $unit->purchase_price ?? 0;
        $residualValue = $unit->residual_value ?? 0;
        $usefulLifeMonths = $unit->useful_life ?? 60; // Default 5 years
        
        $monthlyDepreciation = ($usefulLifeMonths > 0) ? ($purchasePrice - $residualValue) / $usefulLifeMonths : 0;
        
        // Current Book Value
        $purchaseDate = $unit->purchase_date;
        $ageMonths = $purchaseDate ? $purchaseDate->diffInMonths(now()) : 0;
        $accumulatedDepreciation = min($purchasePrice - $residualValue, $monthlyDepreciation * $ageMonths);
        $bookValue = max($residualValue, $purchasePrice - $accumulatedDepreciation);

        $depreciation = [
            'name' => $productName,
            'purchase_price' => $purchasePrice,
            'monthly_depreciation' => round($monthlyDepreciation, 2),
            'accumulated_depreciation' => round($accumulatedDepreciation, 2),
            'book_value' => round($bookValue, 2),
        ];

        // 5. Lost & Damaged Metrics
        $lostDamaged = [];
        if (in_array($unit->condition, ['lost', 'broken']) && $unit->status === ProductUnit::STATUS_RETIRED) {
            // Recalculate based on retirement date (updated_at)
            $retirementDate = $unit->updated_at;
            $ageAtRetirement = $purchaseDate ? $purchaseDate->diffInMonths($retirementDate) : 0;
            $accDeprAtRetirement = min($purchasePrice - $residualValue, $monthlyDepreciation * $ageAtRetirement);
            $bookValueAtRetirement = max($residualValue, $purchasePrice - $accDeprAtRetirement);
            
            $lostDamaged = [
                'name' => $productName,
                'condition' => ucfirst($unit->condition),
                'date_reported' => $retirementDate,
                'purchase_price' => $purchasePrice,
                'accumulated_depreciation' => round($accDeprAtRetirement, 2),
                'book_value' => round($bookValueAtRetirement, 2), // This is the Loss Amount
            ];
        }

        return [
            'utilization' => $utilization,
            'revenue' => $revenue,
            'maintenance' => $maintenance,
            'depreciation' => $depreciation,
            'lost_damaged' => $lostDamaged,
        ];
    }

    public function getDamagePenaltyData(): array
    {
        $totalLateFees = Rental::sum('late_fee');
        
        return [
            [
                'type' => 'Late Fees',
                'description' => 'Fees collected from late returns',
                'amount' => $totalLateFees,
            ],
            [
                'type' => 'Damage Claims',
                'description' => 'Charges for damaged equipment (tracked via Invoice items)',
                'amount' => 0, // Pending implementation
            ]
        ];
    }

    public function getARAgingData(): array
    {
        $invoices = Invoice::where('status', '!=', Invoice::STATUS_PAID)
            ->whereRaw('total > paid_amount')
            ->get();
            
        $summary = [
            '0-30' => 0,
            '31-60' => 0,
            '61-90' => 0,
            '90+' => 0,
        ];
        
        $totalOutstanding = 0;
        
        foreach ($invoices as $invoice) {
            $dueAmount = $invoice->total - $invoice->paid_amount;
            // Calculate days overdue based on due_date
            // If due_date is in the future, diffInDays returns negative, multiply by -1
            // If due_date is in the past, diffInDays returns positive
            // Wait, diffInDays(date, false) returns (date - now) in days.
            // If date is future: positive. If date is past: negative.
            // Let's use standard logic: now()->diffInDays(due_date, false)
            // If due_date is tomorrow: now->diffInDays(tomorrow, false) = 1
            // If due_date was yesterday: now->diffInDays(yesterday, false) = -1
            
            // The original logic was: Carbon::now()->diffInDays($invoice->due_date, false) * -1;
            // If due_date is yesterday (overdue by 1 day): -1 * -1 = 1. Correct.
            // If due_date is tomorrow (not overdue): 1 * -1 = -1. Correct.
            
            $daysOverdue = Carbon::now()->diffInDays($invoice->due_date, false) * -1;
            $daysOverdue = max(0, $daysOverdue);
            
            $totalOutstanding += $dueAmount;
            
            if ($daysOverdue <= 30) {
                $summary['0-30'] += $dueAmount;
            } elseif ($daysOverdue <= 60) {
                $summary['31-60'] += $dueAmount;
            } elseif ($daysOverdue <= 90) {
                $summary['61-90'] += $dueAmount;
            } else {
                $summary['90+'] += $dueAmount;
            }
        }
        
        return [
            'summary' => $summary,
            'total_outstanding' => $totalOutstanding,
        ];
    }

    public function getARAgingPaginator()
    {
        return Invoice::where('status', '!=', Invoice::STATUS_PAID)
            ->whereRaw('total > paid_amount')
            ->orderBy('due_date', 'asc')
            ->paginate(10, ['*'], 'ar_page');
    }

    public function getInvoiceAgingMetrics(Invoice $invoice): array
    {
        $daysOverdue = Carbon::now()->diffInDays($invoice->due_date, false) * -1;
        $dueAmount = $invoice->total - $invoice->paid_amount;
        
        $bucket = '90+';
        if ($daysOverdue <= 30) {
            $bucket = '0-30';
        } elseif ($daysOverdue <= 60) {
            $bucket = '31-60';
        } elseif ($daysOverdue <= 90) {
            $bucket = '61-90';
        }

        return [
            'invoice_number' => $invoice->number,
            'customer' => $invoice->user->name ?? 'Unknown',
            'due_amount' => $dueAmount,
            'days_overdue' => max(0, $daysOverdue),
            'bucket' => $bucket,
        ];
    }

    public function getTrialBalanceData(): array
    {
        // Simple Trial Balance
        $debits = [];
        $credits = [];

        // Assets (Debit)
        foreach (FinanceAccount::all() as $account) {
            $debits[] = ['name' => $account->name, 'amount' => $account->balance];
        }
        
        // AR (Debit)
        $ar = Invoice::where('status', '!=', Invoice::STATUS_PAID)->get()->sum(fn($i) => $i->total - $i->paid_amount);
        $debits[] = ['name' => 'Accounts Receivable', 'amount' => $ar];

        // Expenses (Debit)
        $billExpenses = Bill::sum('amount');
        $directExpenses = Expense::sum('amount');
        $expenses = $billExpenses + $directExpenses;
        $debits[] = ['name' => 'Operating Expenses', 'amount' => $expenses];


        // Liabilities (Credit)
        $ap = Bill::where('status', '!=', Bill::STATUS_PAID)->get()->sum(fn($b) => $b->amount - $b->paid_amount);
        $credits[] = ['name' => 'Accounts Payable', 'amount' => $ap];

        // Income (Credit)
        $invoiceRevenue = Invoice::sum('total');
        $directIncome = FinanceTransaction::where('type', FinanceTransaction::TYPE_INCOME)
            ->whereNull('reference_type')
            ->sum('amount');
        $income = $invoiceRevenue + $directIncome;
        $credits[] = ['name' => 'Revenue', 'amount' => $income];
        
        // Equity (Credit - Balancing figure for now, or Retained Earnings)
        // Asset + Expense = Liability + Equity + Income
        // Equity = (Asset + Expense) - (Liability + Income)
        $totalDebit = collect($debits)->sum('amount');
        $totalCreditBeforeEquity = collect($credits)->sum('amount');
        $equityVal = $totalDebit - $totalCreditBeforeEquity;
        
        $credits[] = ['name' => 'Retained Earnings (Calculated)', 'amount' => $equityVal];

        return [
            'debits' => $debits,
            'credits' => $credits,
            'total_debit' => collect($debits)->sum('amount'),
            'total_credit' => collect($credits)->sum('amount'),
        ];
    }

    public function getTaxSummary(): array
    {
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();

        // Output VAT (from Invoices)
        // Only finalized invoices
        $outputVat = Invoice::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', [
                Invoice::STATUS_SENT, 
                Invoice::STATUS_WAITING_FOR_PAYMENT, 
                Invoice::STATUS_PAID, 
                Invoice::STATUS_PARTIAL
            ])
            ->sum('ppn_amount');

        // Input VAT (from Bills + Expenses)
        $billTax = Bill::whereBetween('bill_date', [$startDate, $endDate])
            ->sum('tax_amount');
            
        $expenseTax = Expense::whereBetween('date', [$startDate, $endDate])
            ->sum('tax_amount');

        $inputVat = $billTax + $expenseTax;

        // PPh 23 (from Invoices - Withheld by customer)
        $pph = Invoice::whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', [
                Invoice::STATUS_SENT, 
                Invoice::STATUS_WAITING_FOR_PAYMENT, 
                Invoice::STATUS_PAID, 
                Invoice::STATUS_PARTIAL
            ])
            ->sum('pph_amount');

        return [
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'net_vat' => $outputVat - $inputVat,
            'pph' => $pph,
        ];
    }

    public function getOutputVatItems()
    {
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();
        
        $query = Invoice::query()
            ->with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', [
                Invoice::STATUS_SENT, 
                Invoice::STATUS_WAITING_FOR_PAYMENT, 
                Invoice::STATUS_PAID, 
                Invoice::STATUS_PARTIAL
            ])
            ->where(function($q) {
                $q->where('ppn_amount', '>', 0)
                  ->orWhere('pph_amount', '>', 0);
            });

        if ($this->searchTax) {
            $query->where(function($q) {
                $q->where('number', 'like', '%' . $this->searchTax . '%')
                  ->orWhere('tax_invoice_number', 'like', '%' . $this->searchTax . '%')
                  ->orWhereHas('user', function($sq) {
                      $sq->where('name', 'like', '%' . $this->searchTax . '%');
                  });
            });
        }

        return $query->latest('date')->paginate(10, ['*'], 'tax_out_page');
    }

    public function getInputVatItems()
    {
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();
        
        // Only Bills for the table
        $query = Bill::query()
            ->whereBetween('bill_date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0);

        return $query->latest('bill_date')->paginate(10, ['*'], 'tax_in_page');
    }

    protected function exportTaxOutput(string $format)
    {
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();
        
        $query = Invoice::query()
            ->with('user')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', [
                Invoice::STATUS_SENT, 
                Invoice::STATUS_WAITING_FOR_PAYMENT, 
                Invoice::STATUS_PAID, 
                Invoice::STATUS_PARTIAL
            ])
            ->where(function($q) {
                $q->where('ppn_amount', '>', 0)
                  ->orWhere('pph_amount', '>', 0);
            });

        if ($this->searchTax) {
            $query->where(function($q) {
                $q->where('number', 'like', '%' . $this->searchTax . '%')
                  ->orWhere('tax_invoice_number', 'like', '%' . $this->searchTax . '%')
                  ->orWhereHas('user', function($sq) {
                      $sq->where('name', 'like', '%' . $this->searchTax . '%');
                  });
            });
        }
        
        $records = $query->latest('date')->get();
        $filename = 'tax-output-report-' . now()->format('Y-m-d');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($records) {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['Date', 'Invoice #', 'Customer', 'Tax Invoice #', 'Tax Base (DPP)', 'PPN (VAT)', 'PPh 23']);
                
                foreach ($records as $invoice) {
                    fputcsv($file, [
                        $invoice->date->format('d/m/Y'),
                        $invoice->number,
                        $invoice->user->name ?? '-',
                        $invoice->tax_invoice_number ?? '-',
                        $invoice->tax_base,
                        $invoice->ppn_amount,
                        $invoice->pph_amount
                    ]);
                }
                fclose($file);
            }, $filename . '.csv');
        } elseif ($format === 'pdf') {
             $pdf = Pdf::loadView('filament.pages.reports.tax-output-pdf', [
                'records' => $records,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'title' => 'Output VAT Report (PPN Keluaran)',
                'date' => now()->format('d M Y')
            ]);
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename . '.pdf');
        }
    }

    protected function exportTaxInput(string $format)
    {
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : now()->startOfMonth();
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfMonth();
        
        $query = Bill::query()
            ->whereBetween('bill_date', [$startDate, $endDate])
            ->where('tax_amount', '>', 0);
            
        $records = $query->latest('bill_date')->get();
        $filename = 'tax-input-report-' . now()->format('Y-m-d');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($records) {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, ['Date', 'Bill #', 'Vendor', 'Tax Invoice #', 'Total Amount', 'VAT Amount']);
                
                foreach ($records as $bill) {
                    fputcsv($file, [
                        $bill->bill_date->format('d/m/Y'),
                        $bill->bill_number,
                        $bill->vendor_name,
                        $bill->tax_invoice_number ?? '-',
                        $bill->amount,
                        $bill->tax_amount
                    ]);
                }
                fclose($file);
            }, $filename . '.csv');
        } elseif ($format === 'pdf') {
             $pdf = Pdf::loadView('filament.pages.reports.tax-input-pdf', [
                'records' => $records,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'title' => 'Input VAT Report (PPN Masukan)',
                'date' => now()->format('d M Y')
            ]);
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename . '.pdf');
        }
    }
}
