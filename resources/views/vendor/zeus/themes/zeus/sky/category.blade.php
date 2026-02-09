<div class="mt-6 container mx-auto px-2 md:px-4">
    <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-md px-6 md:px-10 py-8 mb-8 border-l-8 border-primary-500">
         <div class="flex items-center gap-3 mb-2">
            <span class="uppercase tracking-wider text-xs font-bold text-primary-600 dark:text-primary-400">
                {{ $tag->type }}
            </span>
         </div>
         <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
            {{ $tag->name }}
        </h1>
        @if($tag->description)
            <p class="mt-3 text-lg text-gray-500 dark:text-gray-400 max-w-3xl">
                {{ $tag->description }}
            </p>
        @endif
    </div>

    @unless($posts->isEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            @foreach($posts as $post)
                @include($skyTheme.'.partial.sticky')
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-800 rounded-[2rem] shadow-sm">
            @svg('heroicon-o-document-magnifying-glass', 'w-16 h-16 text-gray-300 mb-4')
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">No posts found</h3>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                There are no posts in this {{ $tag->type }} yet.
            </p>
            <a href="{{ route('blogs') }}" class="mt-6 text-primary-600 hover:text-primary-500 font-medium">
                &larr; Back to all posts
            </a>
        </div>
    @endunless
</div>
