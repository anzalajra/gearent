@php
    if (!isset($tags)) {
        $tags = config('zeus-sky.models.Tag')::withCount('postsPublished')
            ->where('type', 'category')
            ->get();
    }
    if (!isset($recent)) {
        $recent = config('zeus-sky.models.Post')::query()
            ->posts()
            ->published()
            ->whereDate('published_at', '<=', now())
            ->with(['tags', 'author', 'media'])
            ->limit(config('zeus-sky.recentPostsLimit', 5))
            ->orderBy('published_at', 'desc')
            ->get();
    }
@endphp

{{--@include($skyTheme.'.partial.authors')--}}
@include($skyTheme.'.partial.sidebar.search')
@include($skyTheme.'.partial.sidebar.categories')
@include($skyTheme.'.partial.sidebar.recent')
{{--@include($skyTheme.'.partial.sidebar.pages')--}}
