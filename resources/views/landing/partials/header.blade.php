{{-- Header / Navbar --}}
<header
    x-data="{ open: false, scrolled: false }"
    x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })"
    :class="scrolled ? 'bg-white/90 backdrop-blur-md shadow-sm' : 'bg-transparent'"
    class="fixed top-0 inset-x-0 z-50 transition-all duration-300"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 lg:h-20">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">Z</span>
                </div>
                <span class="text-xl font-bold text-gray-900">Zewalo</span>
            </a>

            {{-- Desktop Nav --}}
            <nav class="hidden lg:flex items-center gap-8">
                <a href="#features" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors">Fitur</a>
                <a href="#pricing" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors">Harga</a>
                <a href="#testimonials" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors">Testimoni</a>
            </nav>

            {{-- Desktop CTA --}}
            <div class="hidden lg:flex items-center gap-3">
                <a href="/central" class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors px-4 py-2">
                    Login
                </a>
                <a href="/register-tenant" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors shadow-sm">
                    Daftar Gratis
                </a>
            </div>

            {{-- Mobile Toggle --}}
            <button @click="open = !open" class="lg:hidden p-2 -mr-2 text-gray-600 hover:text-gray-900" aria-label="Menu">
                <svg x-show="!open" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg x-show="open" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="lg:hidden bg-white border-t shadow-lg">
        <div class="px-4 py-4 space-y-3">
            <a href="#features" @click="open = false" class="block text-sm font-medium text-gray-600 hover:text-indigo-600 py-2">Fitur</a>
            <a href="#pricing" @click="open = false" class="block text-sm font-medium text-gray-600 hover:text-indigo-600 py-2">Harga</a>
            <a href="#testimonials" @click="open = false" class="block text-sm font-medium text-gray-600 hover:text-indigo-600 py-2">Testimoni</a>
            <hr class="my-2">
            <a href="/central" class="block text-sm font-medium text-gray-600 hover:text-indigo-600 py-2">Login</a>
            <a href="/register-tenant" class="block w-full text-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">Daftar Gratis</a>
        </div>
    </div>
</header>
