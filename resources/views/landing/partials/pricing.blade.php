{{-- Pricing Section --}}
<section id="pricing" class="py-20 lg:py-28 bg-gray-50" x-data="{ frequency: 'monthly' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-block text-sm font-semibold uppercase tracking-wider text-indigo-600 mb-3">Harga</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">
                Pilih Paket yang <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Tepat</span>
            </h2>
            <p class="mt-4 text-lg text-gray-600">Mulai gratis, upgrade kapan saja sesuai kebutuhan bisnis Anda.</p>
        </div>

        {{-- Frequency Toggle --}}
        <div class="flex justify-center mb-12">
            <div class="inline-flex rounded-full bg-gray-200/80 p-1">
                <button
                    @click="frequency = 'monthly'"
                    :class="frequency === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="relative rounded-full px-5 py-2 text-sm font-semibold transition-all duration-200"
                >
                    Bulanan
                </button>
                <button
                    @click="frequency = 'yearly'"
                    :class="frequency === 'yearly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                    class="relative rounded-full px-5 py-2 text-sm font-semibold transition-all duration-200 flex items-center gap-2"
                >
                    Tahunan
                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Hemat 17%</span>
                </button>
            </div>
        </div>

        {{-- Pricing Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 max-w-5xl mx-auto">
            {{-- Free Plan --}}
            <div class="relative flex flex-col rounded-2xl border border-gray-200 bg-white p-8 transition-all hover:shadow-lg hover:-translate-y-1 duration-300">
                <h3 class="text-xl font-bold text-gray-900">Free</h3>
                <p class="mt-1 text-sm text-gray-500">Sempurna untuk memulai</p>

                <div class="mt-6 flex items-baseline">
                    <span class="text-4xl font-extrabold text-gray-900">Gratis</span>
                </div>

                <ul class="mt-8 space-y-3 flex-1">
                    @foreach(['1 pengguna', '10 produk', '100 MB penyimpanan', '1 domain', 'Dukungan email', 'Notifikasi dasar'] as $feat)
                    <li class="flex items-center gap-3 text-sm text-gray-600">
                        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>

                <a href="/register-tenant" class="mt-8 block w-full rounded-xl border-2 border-gray-200 bg-white py-3 text-center text-sm font-semibold text-gray-900 hover:bg-gray-50 transition-colors">
                    Mulai Gratis
                </a>
            </div>

            {{-- Basic Plan (Popular) --}}
            <div class="relative flex flex-col rounded-2xl border-2 border-indigo-600 bg-white p-8 shadow-xl shadow-indigo-600/10 transition-all hover:-translate-y-1 duration-300">
                {{-- Popular Badge --}}
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-600 px-4 py-1.5 text-xs font-bold text-white shadow-lg">
                        🔥 Paling Populer
                    </span>
                </div>

                <h3 class="text-xl font-bold text-gray-900">Basic</h3>
                <p class="mt-1 text-sm text-gray-500">Cocok untuk bisnis kecil</p>

                <div class="mt-6 flex items-baseline gap-1">
                    <span class="text-4xl font-extrabold text-gray-900" x-text="frequency === 'monthly' ? 'Rp 99rb' : 'Rp 82.5rb'">Rp 99rb</span>
                    <span class="text-sm text-gray-500">/bulan</span>
                </div>
                <p x-show="frequency === 'yearly'" x-cloak class="text-xs text-emerald-600 font-medium mt-1">Rp 990.000/tahun — hemat Rp 198.000</p>

                <ul class="mt-8 space-y-3 flex-1">
                    @foreach(['3 pengguna', '100 produk', '1 GB penyimpanan', '2 domain', 'Dukungan prioritas', 'Analytics', 'Custom branding', 'Invoice & Quotation'] as $feat)
                    <li class="flex items-center gap-3 text-sm text-gray-600">
                        <svg class="w-5 h-5 text-indigo-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>

                <a href="/register-tenant" class="mt-8 block w-full rounded-xl bg-indigo-600 py-3 text-center text-sm font-semibold text-white hover:bg-indigo-700 transition-colors shadow-sm">
                    Mulai 14 Hari Gratis
                    <svg class="inline-block ml-1 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>

            {{-- Pro Plan --}}
            <div class="relative flex flex-col rounded-2xl bg-gray-900 text-white p-8 transition-all hover:shadow-lg hover:-translate-y-1 duration-300 sm:col-span-2 xl:col-span-1">
                {{-- Grid bg --}}
                <div class="absolute inset-0 rounded-2xl bg-[linear-gradient(to_right,#4f4f4f2e_1px,transparent_1px),linear-gradient(to_bottom,#4f4f4f2e_1px,transparent_1px)] bg-[size:40px_40px] [mask-image:radial-gradient(ellipse_80%_50%_at_50%_0%,#000_70%,transparent_110%)] pointer-events-none"></div>

                <h3 class="text-xl font-bold relative z-10">Pro</h3>
                <p class="mt-1 text-sm text-gray-400 relative z-10">Untuk bisnis yang berkembang</p>

                <div class="mt-6 flex items-baseline gap-1 relative z-10">
                    <span class="text-4xl font-extrabold" x-text="frequency === 'monthly' ? 'Rp 299rb' : 'Rp 249rb'">Rp 299rb</span>
                    <span class="text-sm text-gray-400">/bulan</span>
                </div>
                <p x-show="frequency === 'yearly'" x-cloak class="text-xs text-emerald-400 font-medium mt-1 relative z-10">Rp 2.990.000/tahun — hemat Rp 598.000</p>

                <ul class="mt-8 space-y-3 flex-1 relative z-10">
                    @foreach(['10 pengguna', '1.000 produk', '10 GB penyimpanan', '5 domain', 'Dukungan premium 24/7', 'Analytics lanjutan', 'Custom branding', 'API access', 'White-label', 'Semua fitur Basic'] as $feat)
                    <li class="flex items-center gap-3 text-sm text-gray-300">
                        <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>

                <a href="/register-tenant" class="relative z-10 mt-8 block w-full rounded-xl bg-white py-3 text-center text-sm font-semibold text-gray-900 hover:bg-gray-100 transition-colors">
                    Hubungi Kami
                    <svg class="inline-block ml-1 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
            </div>
        </div>
    </div>
</section>
