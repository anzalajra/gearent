{{-- CTA Section --}}
<section class="relative py-20 lg:py-28 overflow-hidden">
    {{-- Gradient Background --}}
    <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-700"></div>
    {{-- Grid Pattern Overlay --}}
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff0d_1px,transparent_1px),linear-gradient(to_bottom,#ffffff0d_1px,transparent_1px)] bg-[size:40px_40px]"></div>
    {{-- Radial glow --}}
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-purple-500/30 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight">
            Siap Digitalkan Bisnis<br class="hidden sm:inline"> Rental Anda?
        </h2>
        <p class="mt-6 text-lg text-indigo-100 max-w-2xl mx-auto">
            Bergabung dengan 2.500+ bisnis rental yang sudah menggunakan Zewalo. Setup 5 menit, tanpa kartu kredit.
        </p>

        {{-- CTA Buttons --}}
        <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4">
            <a href="/register-tenant" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white text-indigo-700 font-bold text-base hover:bg-gray-100 transition-all shadow-xl shadow-black/10 hover:scale-105 hover:-translate-y-0.5 duration-200">
                Daftar Gratis Sekarang
                <svg class="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="#features" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white/10 text-white font-semibold text-base border border-white/20 hover:bg-white/20 transition-all duration-200 backdrop-blur-sm">
                <svg class="mr-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Lihat Demo
            </a>
        </div>

        {{-- Trust badges --}}
        <div class="mt-12 flex flex-wrap justify-center items-center gap-6 text-sm text-indigo-200">
            <span class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                SSL & Enkripsi
            </span>
            <span class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                99.9% Uptime
            </span>
            <span class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Tanpa Kartu Kredit
            </span>
            <span class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Setup 5 Menit
            </span>
        </div>
    </div>
</section>
