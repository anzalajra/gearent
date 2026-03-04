{{-- Footer --}}
<footer class="bg-gray-900 text-gray-400">
    {{-- Main Footer --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 lg:gap-12">
            {{-- Brand Column --}}
            <div class="col-span-2 md:col-span-4 lg:col-span-2">
                <a href="/" class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-tr from-indigo-600 to-purple-500 flex items-center justify-center">
                        <span class="text-white font-extrabold text-base">Z</span>
                    </div>
                    <span class="text-xl font-bold text-white">Zewalo</span>
                </a>
                <p class="mt-4 text-sm leading-relaxed max-w-sm">
                    Platform all-in-one untuk mengelola bisnis rental Anda secara digital. Dari inventaris, booking, hingga keuangan — semuanya di satu tempat.
                </p>
                {{-- Social Links --}}
                <div class="flex gap-3 mt-6">
                    @foreach([
                        ['label' => 'Instagram', 'icon' => 'M7.8 2h8.4C19.4 2 22 4.6 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8C4.6 22 2 19.4 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2m-.2 2A3.6 3.6 0 0 0 4 7.6v8.8C4 18.39 5.61 20 7.6 20h8.8a3.6 3.6 0 0 0 3.6-3.6V7.6C20 5.61 18.39 4 16.4 4H7.6m9.65 1.5a1.25 1.25 0 0 1 1.25 1.25A1.25 1.25 0 0 1 17.25 8 1.25 1.25 0 0 1 16 6.75a1.25 1.25 0 0 1 1.25-1.25M12 7a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 2a3 3 0 0 0-3 3 3 3 0 0 0 3 3 3 3 0 0 0 3-3 3 3 0 0 0-3-3z'],
                        ['label' => 'Twitter', 'icon' => 'M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z'],
                        ['label' => 'Facebook', 'icon' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z'],
                        ['label' => 'YouTube', 'icon' => 'M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.1c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z M9.75 15.02l5.75-3.27-5.75-3.27v6.54z'],
                    ] as $social)
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 hover:bg-indigo-600 flex items-center justify-center transition-colors duration-200" title="{{ $social['label'] }}">
                        <svg class="w-4 h-4 text-gray-400 hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $social['icon'] }}"/></svg>
                    </a>
                    @endforeach
                </div>
            </div>

            {{-- Produk --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Produk</h4>
                <ul class="space-y-3">
                    @foreach(['Fitur' => '#features', 'Harga' => '#pricing', 'Demo' => '#', 'Integrasi' => '#', 'Changelog' => '#'] as $label => $href)
                    <li><a href="{{ $href }}" class="text-sm hover:text-white transition-colors duration-200">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Perusahaan --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Perusahaan</h4>
                <ul class="space-y-3">
                    @foreach(['Tentang Kami' => '#', 'Blog' => '#', 'Karir' => '#', 'Kontak' => '#', 'Partner' => '#'] as $label => $href)
                    <li><a href="{{ $href }}" class="text-sm hover:text-white transition-colors duration-200">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Bantuan --}}
            <div>
                <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Bantuan</h4>
                <ul class="space-y-3">
                    @foreach(['Pusat Bantuan' => '#', 'Dokumentasi' => '#', 'Status' => '#', 'Syarat & Ketentuan' => '#', 'Kebijakan Privasi' => '#'] as $label => $href)
                    <li><a href="{{ $href }}" class="text-sm hover:text-white transition-colors duration-200">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    {{-- Bottom footer --}}
    <div class="border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-sm">&copy; {{ date('Y') }} Zewalo. All rights reserved.</p>
            <div class="flex items-center gap-4 text-sm">
                <a href="#" class="hover:text-white transition-colors">Privasi</a>
                <span class="text-gray-700">|</span>
                <a href="#" class="hover:text-white transition-colors">Syarat Layanan</a>
                <span class="text-gray-700">|</span>
                <a href="#" class="hover:text-white transition-colors">Cookie</a>
            </div>
        </div>
    </div>
</footer>
