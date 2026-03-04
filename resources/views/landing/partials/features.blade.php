{{-- Features Section --}}
<section id="features" class="py-20 lg:py-28 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="inline-block text-sm font-semibold uppercase tracking-wider text-indigo-600 mb-3">Fitur Lengkap</span>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900">
                Semua yang Anda butuhkan
                <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">dalam satu platform</span>
            </h2>
            <p class="mt-4 text-lg text-gray-600">Fitur-fitur powerful yang dirancang khusus untuk bisnis penyewaan.</p>
        </div>

        {{-- Features Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 relative">
            @php
                $features = [
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
                        'title' => 'Manajemen Inventaris',
                        'desc' => 'Kelola produk, unit, variasi, dan ketersediaan secara real-time dengan barcode scanner.',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                        'title' => 'Booking & Rental',
                        'desc' => 'Sistem pemesanan lengkap dengan kalender, cek ketersediaan, dan pengelolaan masa sewa.',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
                        'title' => 'Keuangan & Invoice',
                        'desc' => 'Buat invoice, quotation, dan kelola arus kas otomatis dengan laporan keuangan lengkap.',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
                        'title' => 'Manajemen Pelanggan',
                        'desc' => 'Database pelanggan dengan kategori, dokumen, riwayat sewa, dan portal pelanggan.',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
                        'title' => 'Analytics & Laporan',
                        'desc' => 'Dashboard real-time dengan grafik pendapatan, utilitas unit, dan laporan lengkap.',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
                        'title' => 'Mobile Friendly',
                        'desc' => 'Akses dari smartphone dengan PWA. Scan QR code, terima notifikasi real-time.',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
                        'title' => 'Multi-tenant & Aman',
                        'desc' => 'Database terpisah untuk setiap toko. Data Anda aman dan terisolasi sepenuhnya.',
                    ],
                    [
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
                        'title' => 'Setup Instan',
                        'desc' => 'Toko siap dalam hitungan detik. Tanpa instalasi, tanpa konfigurasi rumit.',
                    ],
                ];
            @endphp

            @foreach($features as $index => $feature)
            <div class="group/feature relative flex flex-col py-8 lg:py-10 px-8 lg:border-r border-gray-100 {{ ($index === 0 || $index === 4) ? 'lg:border-l' : '' }} {{ $index < 4 ? 'lg:border-b' : '' }} border-b lg:border-b-{{ $index < 4 ? 'gray-100' : '0' }}">
                {{-- Hover Gradient --}}
                @if($index < 4)
                <div class="opacity-0 group-hover/feature:opacity-100 transition duration-300 absolute inset-0 h-full w-full bg-gradient-to-t from-indigo-50/80 to-transparent pointer-events-none"></div>
                @else
                <div class="opacity-0 group-hover/feature:opacity-100 transition duration-300 absolute inset-0 h-full w-full bg-gradient-to-b from-indigo-50/80 to-transparent pointer-events-none"></div>
                @endif

                {{-- Icon --}}
                <div class="mb-4 relative z-10 text-gray-500 group-hover/feature:text-indigo-600 transition-colors">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $feature['icon'] !!}</svg>
                </div>

                {{-- Title with left bar --}}
                <div class="text-base lg:text-lg font-bold mb-2 relative z-10">
                    <div class="absolute left-0 inset-y-0 h-6 group-hover/feature:h-8 w-1 rounded-tr-full rounded-br-full bg-gray-200 group-hover/feature:bg-indigo-500 transition-all duration-200 origin-center -ml-8"></div>
                    <span class="group-hover/feature:translate-x-1 transition duration-200 inline-block text-gray-800">
                        {{ $feature['title'] }}
                    </span>
                </div>

                {{-- Description --}}
                <p class="text-sm text-gray-500 relative z-10 leading-relaxed">
                    {{ $feature['desc'] }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
</section>
