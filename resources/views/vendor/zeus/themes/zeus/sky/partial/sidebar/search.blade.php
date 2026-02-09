<div class="my-4">
    <div class="flex flex-col max-w-sm py-4 mx-auto">
        <form method="GET" class="relative">
            <input 
                class="w-full pl-4 pr-10 py-2 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white" 
                type="text" 
                name="search" 
                id="search" 
                value="{{ request()->get('search') }}"
                placeholder="Search..."
            >
            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primary-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </button>
        </form>
    </div>
</div>
