<x-filament-panels::page>
    {{-- Progress Indicator --}}
    @if($this->isProcessing)
        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
            <div class="flex items-center gap-3">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                <div class="flex-1">
                    <h4 class="font-medium text-blue-900 dark:text-blue-400">{{ $this->currentOperation }}</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300">{{ $this->progressMessage }}</p>
                    @if($this->progressPercent > 0)
                        <div class="mt-2 w-full bg-blue-200 dark:bg-blue-800 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $this->progressPercent }}%"></div>
                        </div>
                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">{{ $this->progressPercent }}% complete</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
