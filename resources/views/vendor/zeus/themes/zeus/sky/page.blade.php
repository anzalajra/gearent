<div class="">
    {{-- Breadcrumbs --}}
    @if($post->parent !== null)
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('page',[$post->parent->slug]) }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-primary-600 dark:text-gray-400 dark:hover:text-white">
                        {{ $post->parent->title }}
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">{{ $post->title }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    @endif

    {{-- Header / Title --}}
    @if(data_get($post->options, 'show_title', true))
        <div class="mb-8 border-b border-gray-200 pb-8">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">{{ $post->title }}</h1>
            @if($post->description)
                <p class="mt-4 text-xl text-gray-500 dark:text-gray-400">{{ $post->description }}</p>
            @endif

            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center space-x-4 text-sm text-gray-500">
                    @unless ($post->tags->isEmpty())
                        <div class="flex gap-2">
                            @each($skyTheme.'.partial.category', $post->tags->where('type','category'), 'category')
                        </div>
                    @endunless
                </div>
            </div>
        </div>
    @endif

    {{-- Featured Image --}}
    @if($post->image('pages') !== null)
        <div class="mb-10">
            <img alt="{{ $post->title }}" src="{{ $post->image('pages') }}" class="w-full rounded-xl shadow-lg object-cover max-h-[500px]"/>
        </div>
    @endif

    {{-- Content --}}
    <div class="prose prose-lg max-w-none dark:prose-invert prose-a:text-primary-600 hover:prose-a:text-primary-500">
        {!! $post->getContent() !!}
    </div>

    {{-- Children Pages --}}
    @if(!$children->isEmpty())
        <div class="mt-16 pt-8 border-t border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Sub Pages</h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($children as $post)
                    @include($skyTheme.'.partial.children-pages')
                @endforeach
            </div>
        </div>
    @endif
</div>
