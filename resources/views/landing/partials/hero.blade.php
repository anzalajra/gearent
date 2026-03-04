{{-- Hero Section --}}
<section class="relative overflow-hidden bg-gradient-to-b from-indigo-50/80 via-white to-white pt-32 lg:pt-40 pb-20 lg:pb-32">
    {{-- Background Decorations --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-indigo-200/30 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-purple-200/30 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-100/20 rounded-full blur-3xl"></div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        {{-- Eyebrow --}}
        <div class="animate-appear opacity-0">
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-100 px-4 py-1.5 text-xs font-semibold uppercase tracking-wider text-indigo-700">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-600"></span>
                </span>
                Platform Rental Management #1
            </span>
        </div>

        {{-- Heading --}}
        <h1 class="mt-8 text-4xl sm:text-5xl lg:text-7xl font-extrabold tracking-tight animate-appear opacity-0 [animation-delay:100ms]">
            <span class="text-gray-900">Kelola Bisnis Rental</span>
            <br>
            <span class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent">
                Lebih Mudah & Cepat
            </span>
        </h1>

        {{-- Subtitle --}}
        <p class="mt-6 max-w-2xl mx-auto text-lg sm:text-xl text-gray-600 leading-relaxed animate-appear opacity-0 [animation-delay:300ms]">
            Platform all-in-one untuk mengelola penyewaan, inventaris, pelanggan, dan keuangan bisnis rental Anda. Mulai dalam hitungan menit.
        </p>

        {{-- CTA Buttons --}}
        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 animate-appear opacity-0 [animation-delay:500ms]">
            <a href="/register-tenant" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl bg-indigo-600 px-8 py-4 text-base font-semibold text-white hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-600/25 hover:shadow-xl hover:shadow-indigo-600/30 hover:-translate-y-0.5">
                Mulai Gratis 14 Hari
                <svg class="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
            <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-8 py-4 text-base font-semibold text-gray-700 hover:bg-gray-50 transition-all hover:-translate-y-0.5">
                <svg class="mr-2 w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Lihat Demo
            </a>
        </div>

        {{-- Social Proof --}}
        <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-6 animate-appear opacity-0 [animation-delay:700ms]">
            <div class="flex -space-x-3">
                @foreach(range(1, 5) as $i)
                <div class="w-10 h-10 rounded-full border-2 border-white bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                    {{ chr(64 + $i) }}
                </div>
                @endforeach
            </div>
            <div class="text-left">
                <div class="flex items-center gap-0.5">
                    @foreach(range(1, 5) as $i)
                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    @endforeach
                </div>
                <p class="text-sm text-gray-500">Dipercaya <strong class="text-gray-700">200+</strong> bisnis rental</p>
            </div>
        </div>

        {{-- Mockup Dashboard --}}
        <div class="mt-16 lg:mt-20 relative animate-appear opacity-0 [animation-delay:900ms]">
            <div class="relative mx-auto max-w-5xl">
                {{-- Browser Frame --}}
                <div class="rounded-xl bg-gray-900/5 p-2 ring-1 ring-gray-900/10 backdrop-blur">
                    <div class="rounded-lg bg-white shadow-2xl ring-1 ring-gray-900/5 overflow-hidden">
                        {{-- Browser Bar --}}
                        <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3 bg-gray-50">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="flex-1 flex justify-center">
                                <div class="flex items-center gap-2 bg-gray-100 rounded-md px-4 py-1 text-xs text-gray-500 min-w-[200px]">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    toko-anda.zewalo.test/admin
                                </div>
                            </div>
                        </div>
                        {{-- Dashboard Preview --}}
                        <div class="p-6 bg-gradient-to-br from-gray-50 to-white min-h-[300px] lg:min-h-[400px]">
                            {{-- Stats Row --}}
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                                @foreach([
                                    ['Total Rental', '1,234', 'text-indigo-600', 'bg-indigo-50'],
                                    ['Pendapatan', 'Rp 45.6M', 'text-emerald-600', 'bg-emerald-50'],
                                    ['Pelanggan', '567', 'text-purple-600', 'bg-purple-50'],
                                    ['Unit Tersedia', '89', 'text-amber-600', 'bg-amber-50'],
                                ] as [$label, $value, $textColor, $bgColor])
                                <div class="rounded-lg {{ $bgColor }} p-4">
                                    <p class="text-xs text-gray-500 mb-1">{{ $label }}</p>
                                    <p class="text-lg lg:text-xl font-bold {{ $textColor }}">{{ $value }}</p>
                                </div>
                                @endforeach
                            </div>
                            {{-- Chart Placeholder --}}
                            <div class="rounded-lg border border-gray-200 bg-white p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <p class="text-sm font-medium text-gray-700">Grafik Pendapatan</p>
                                    <span class="text-xs text-gray-400">7 hari terakhir</span>
                                </div>
                                <div class="flex items-end gap-2 h-24">
                                    @foreach([40, 65, 45, 80, 55, 90, 70] as $h)
                                    <div class="flex-1 bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t-sm opacity-80" style="height: {{ $h }}%"></div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Gradient fade --}}
                <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-white to-transparent pointer-events-none"></div>
            </div>
        </div>
    </div>
</section>
