<a href="{{ route('page',$post->slug) }}" class="group block h-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 p-5">
    <div class="flex items-start space-x-4 rtl:space-x-reverse">
        @if($post->image() !== null)
            <img alt="{{ $post->title }}" src="{{ $post->image() }}" class="h-14 w-14 rounded-lg object-cover group-hover:opacity-90 transition-opacity flex-shrink-0"/>
        @else
             <div class="h-14 w-14 rounded-lg bg-gray-100 dark:bg-gray-700 flex flex-shrink-0 items-center justify-center text-gray-400">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
             </div>
        @endif
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 transition-colors truncate">
                {{ $post->title ?? '' }}
            </h3>
            @if($post->description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                    {{ $post->description }}
                </p>
            @endif
        </div>
    </div>
</a>
