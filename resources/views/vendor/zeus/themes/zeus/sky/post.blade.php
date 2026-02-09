<div class="mt-6 container mx-auto px-2 md:px-4">
    <x-slot name="header">
        <span class="capitalize">{{ $post->title }}</span>
    </x-slot>

    <x-slot name="breadcrumbs">
        <li class="flex items-center">
            <a href="{{ route('blogs') }}">{{ __('zeus-sky::cms.post.title') }}</a>
            @svg('heroicon-s-arrow-small-right','fill-current w-4 h-4 mx-3 rtl:rotate-180')
        </li>
        <li class="flex items-center">
            {{ $post->title }}
        </li>
    </x-slot>

    <div class="flex flex-col sm:flex-row justify-between mx-auto gap-3 md:gap-6 py-4 md:py-8">
        {{-- Main Content --}}
        <section class="w-full sm:w-2/3 lg:w-3/4">
            @if($post->image() !== null)
                <img alt="{{ $post->title }}" src="{{ $post->image() }}" class="mb-6 w-full h-auto shadow-md rounded-[2rem] rounded-bl-none z-0 object-cover"/>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-[2rem] rounded-tl-none shadow-md px-6 md:px-10 pb-6 pt-6">
                
                {{-- 1. Title Post (Unclickable) --}}
                <h1 class="text-3xl font-bold text-gray-700 dark:text-gray-100 mb-4">
                    {{ $post->title ?? '' }}
                </h1>

                {{-- Metadata --}}
                <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-6 pb-4 border-b border-gray-100 dark:border-gray-700">
                    {{-- 2. Uploaded Date --}}
                    <span class="flex items-center gap-1" title="Published Date">
                        @svg('heroicon-o-calendar', 'w-4 h-4')
                        {{ optional($post->published_at)->diffForHumans() ?? '' }}
                    </span>

                    {{-- 3. Tags --}}
                    @unless ($post->tags->where('type','tag')->isEmpty())
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-tag', 'w-4 h-4')
                            @foreach($post->tags->where('type','tag') as $tag)
                                <a href="{{ route($tag->type, $tag->slug) }}" class="hover:text-primary-500 transition-colors">
                                    {{ $tag->name }}
                                </a>
                                @if(!$loop->last) <span class="text-gray-300">|</span> @endif
                            @endforeach
                        </div>
                    @endunless

                    {{-- 4. Author (Unclickable) --}}
                    <div class="flex items-center gap-2 ml-auto">
                        <span>By</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-200">
                            {{ $post->author->name ?? '' }}
                        </span>
                    </div>
                </div>

                {{-- Content --}}
                <div class="prose dark:prose-invert max-w-none">
                    {!! $post->getContent() !!}
                </div>
            </div>

            @if($related->isNotEmpty())
                <div class="py-6 flex flex-col mt-4 gap-4">
                    <h1 class="text-xl font-bold text-gray-700 dark:text-gray-100 md:text-2xl">{{ __('zeus-sky::cms.post.related_posts') }}</h1>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($related as $post)
                            @include($skyTheme.'.partial.related')
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- Sidebar --}}
        <nav class="w-full sm:w-1/3 lg:w-1/4">
            @include($skyTheme.'.partial.sidebar')
        </nav>
    </div>
</div>
