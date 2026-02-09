<a href="{{ route($tag->type, $tag->slug) }}" class="text-gray-400">
    {{ $tag->name ?? '' }}
    @if(!$loop->last) - @endif
</a>
