<div class="fi-ta-text">
    @if($getRecord()->password !== null)
        <span x-tooltip.raw="{{ __('zeus-sky::cms.post.password_protected') }}" title="{{ __('Password Protected') }}">
            @svg('heroicon-s-lock-closed','w-4 h-4 inline-flex text-danger-600')
        </span>
    @endif
    {{ str($getRecord()->title)->limit(50) }}
</div>
