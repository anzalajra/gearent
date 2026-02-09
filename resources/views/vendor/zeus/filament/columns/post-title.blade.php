<div class="fi-ta-text">
    @if($getRecord()->password !== null)
        <span x-tooltip.raw="{{ __('zeus-sky::cms.post.password_protected') }}" title="{{ __('zeus-sky::cms.post.password_protected') }}">
            @svg('heroicon-s-lock-closed','w-4 h-4 inline-flex text-danger-600')
        </span>
    @endif

    @if($getRecord()->sticky_until !== null)
        <span x-tooltip.raw="{{ __('zeus-sky::cms.post.sticky_until') }} {{ $getRecord()->sticky_until->diffForHumans() }}" title="{{ __('zeus-sky::cms.post.sticky_until') }} {{ $getRecord()->sticky_until->diffForHumans() }}">
            @svg('tabler-pin','w-4 h-4 inline-flex text-primary-600')
        </span>
    @endif

    {{ str($getRecord()->title)->limit(50) }}
</div>
