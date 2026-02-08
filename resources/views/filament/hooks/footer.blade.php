<div class="flex flex-col items-center justify-center py-4 text-xs text-gray-500">
    <div>
        {{ config('app.name') }} {{ \App\Helpers\SystemVersion::getName() }}
    </div>
    <div class="text-[10px] text-gray-400">
        {{ \App\Helpers\SystemVersion::getReleaseDate() }}
    </div>
</div>
