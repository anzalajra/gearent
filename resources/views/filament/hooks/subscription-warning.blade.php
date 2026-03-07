<div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
    <div class="flex items-center gap-3">
        <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
        <p class="flex-1 text-sm font-medium text-amber-800 dark:text-amber-200">
            {{ $message }}
        </p>
        <a href="{{ $billingUrl }}"
           class="shrink-0 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-amber-700">
            Perpanjang Sekarang
        </a>
    </div>
</div>
