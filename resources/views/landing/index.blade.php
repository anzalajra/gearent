<!DOCTYPE html>

<html class="" lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Zewalo - Kelola Bisnis Rental Anda Lebih Mudah &amp; Efisien</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#14B8A6",
                        "background-light": "#ffffff",
                        "background-dark": "#f8fafc",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.5rem",
                        "lg": "0.75rem",
                        "xl": "1rem",
                        "full": "9999px"
                    },
                },
            },
        }</script>
</head>
<body class="bg-white font-display text-slate-900 antialiased">
<!-- Header / Navbar -->
<header class="sticky top-0 z-50 w-full border-b border-slate-200 bg-background-light/80 backdrop-blur-md bg-white/80">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="flex h-16 items-center justify-between">
<div class="flex items-center gap-2">
<div class="text-primary">
<svg class="h-8 w-8" fill="currentColor" viewbox="0 0 48 48">
<path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z"></path>
</svg>
</div>
<span class="text-xl font-bold tracking-tight">Zewalo</span>
</div>
<nav class="hidden md:flex items-center gap-8">
<a class="text-sm font-medium hover:text-primary transition-colors" href="#features">Features</a>
<a class="text-sm font-medium hover:text-primary transition-colors" href="#solutions">Solutions</a>
<a class="text-sm font-medium hover:text-primary transition-colors" href="{{ route('landing.pricing') }}">Pricing</a>
<a class="text-sm font-medium hover:text-primary transition-colors" href="#testimonials">Testimonials</a>
</nav>
<div class="flex items-center gap-3">
<button class="hidden sm:block text-sm font-semibold px-4 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">Login</button>
<button class="bg-primary hover:bg-primary/90 text-white text-sm font-bold px-5 py-2.5 rounded-lg transition-all shadow-lg shadow-primary/20">Get Started</button>
</div>
</div>
</div>
</header>
<!-- Hero Section -->
<section class="relative pt-20 pb-16 lg:pt-32 lg:pb-24 overflow-hidden">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
<div class="max-w-3xl mx-auto">
<h1 class="text-4xl sm:text-6xl font-black tracking-tight mb-6 leading-[1.1]">
                    Kelola Bisnis Rental Anda Lebih <span class="text-primary">Mudah &amp; Efisien</span>
</h1>
<p class="text-lg sm:text-xl text-slate-600 dark:text-slate-400 mb-10 leading-relaxed">
                    Platform manajemen all-in-one untuk rental kamera, alat camping, mobil, motor, dan banyak lagi. Kendalikan inventaris dan pesanan dalam satu dashboard.
                </p>
<div class="flex flex-col sm:flex-row items-center justify-center gap-4">
<button class="w-full sm:w-auto bg-primary hover:bg-primary/90 text-white text-lg font-bold px-8 py-4 rounded-xl transition-all shadow-xl shadow-primary/25">
                        Coba Gratis 14 Hari
                    </button>
<button class="w-full sm:w-auto bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 font-semibold px-8 py-4 rounded-xl transition-all">
                        Lihat Demo
                    </button>
</div>
</div>
<!-- Hero Illustration Placeholder -->
<div class="mt-16 relative rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-2xl">
<div class="aspect-video w-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-background-dark flex items-center justify-center" data-alt="Modern abstract dashboard UI illustration showing rental statistics and calendars">
<span class="material-symbols-outlined text-9xl text-primary/20">dashboard</span>
</div>
</div>
</div>
<!-- Background Decor -->
<div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full pointer-events-none -z-10 opacity-20">
<div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary rounded-full blur-[120px]"></div>
<div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-blue-400 rounded-full blur-[100px]"></div>
</div>
</section>
<!-- Features Section -->
<section class="py-24 bg-slate-50/50">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="text-center mb-16">
<h2 class="text-4xl font-black mb-4 tracking-tight">Fitur Unggulan</h2>
<p class="text-slate-600 max-w-2xl mx-auto">Solusi lengkap untuk mengoptimalkan operasional bisnis rental Anda dengan teknologi terkini.</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6"><!-- Feature 1: Rental live stock -->
<div class="group relative overflow-hidden bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
<div>
<div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
<span class="material-symbols-outlined text-4xl">inventory_2</span>
</div>
<h3 class="text-xl font-bold mb-3">Rental Live Stock</h3>
<p class="text-slate-600 text-sm leading-relaxed">
            Pantau stok unit rental secara real-time di berbagai lokasi dalam satu tampilan layar.
        </p>
</div>
</div>
<!-- Feature 2: Advanced inventory management -->
<div class="md:col-span-2 group relative overflow-hidden bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
<div>
<div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
<span class="material-symbols-outlined text-4xl">settings_suggest</span>
</div>
<h3 class="text-2xl font-bold mb-3">Advanced Inventory Management</h3>
<p class="text-slate-600 leading-relaxed max-w-md">
            Kelola detail aset, jadwal servis, hingga riwayat penggunaan setiap unit secara mendalam untuk memperpanjang usia aset Anda.
        </p>
</div>
</div>
<!-- Feature 3: Booking Online -->
<div class="group relative overflow-hidden bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
<div>
<div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
<span class="material-symbols-outlined text-4xl">calendar_month</span>
</div>
<h3 class="text-xl font-bold mb-3">Booking Online</h3>
<p class="text-slate-600 text-sm leading-relaxed">
            Terima pesanan pelanggan 24/7 melalui portal khusus yang terintegrasi langsung dengan jadwal stok.
        </p>
</div>
</div>
<!-- Feature 4: Quotation & Invoicing -->
<div class="group relative overflow-hidden bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
<div>
<div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
<span class="material-symbols-outlined text-4xl">receipt_long</span>
</div>
<h3 class="text-xl font-bold mb-3">Quotation &amp; Invoicing</h3>
<p class="text-slate-600 text-sm leading-relaxed">
            Buat penawaran harga dan invoice otomatis yang profesional langsung dari sistem.
        </p>
</div>
</div>
<!-- Feature 5: Laporan -->
<div class="group relative overflow-hidden bg-white p-8 rounded-3xl border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
<div>
<div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
<span class="material-symbols-outlined text-4xl">analytics</span>
</div>
<h3 class="text-xl font-bold mb-3">Laporan Rental &amp; Keuangan</h3>
<p class="text-slate-600 text-sm leading-relaxed">
            Dapatkan laporan performa unit rental dan laporan keuangan (laba rugi) secara otomatis.
        </p>
</div>
</div></div>
</div>
</section>
<!-- Case Studies Section -->
<section class="py-20">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<h2 class="text-3xl font-bold mb-12">Studi Kasus Pelanggan</h2>
<div class="grid md:grid-cols-3 gap-6">
<!-- Case 1 -->
<div class="group cursor-pointer">
<div class="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" data-alt="Professional camera equipment and lenses on a shelf" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAktuZHqGb-hgugBxWlc5aeuJXMrrJthia9WVy8K2adRlpBEcGbuK76Zp9ZckrIIIY1TK5OmJePbiJdm10l_HDHH0pbbXBMJQ92-Mg4yO5Kh8JLtzWgvy3JlPxpdSXQEPVjJO6xpJ0H1iDL_qRghA4nM_X40cWTAI_B5D6UNwgRNjVI_M0VZF4fq9YhLFXZiZeVWHVxXUj8BqxEvr1g7Q_F4tD2Uy94MIQj2cz8kfKF0z2ajUL5KAkrTQcH9wEl4a-KhGMaNMT4mXi-"/>
<div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
<div class="absolute bottom-4 left-4">
<span class="bg-primary text-xs font-bold text-white px-2 py-1 rounded">Fotografi</span>
</div>
</div>
<h4 class="text-lg font-bold">Rental Kamera Pro</h4>
<p class="text-slate-600 dark:text-slate-400 text-sm">Optimasi stok hingga 40% dan peningkatan efisiensi pengembalian unit.</p>
</div>
<!-- Case 2 -->
<div class="group cursor-pointer">
<div class="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" data-alt="Camping tents and outdoor gear in nature" src="https://lh3.googleusercontent.com/aida-public/AB6AXuA18_UZWWXMMjBp1Qhkx2mruyIJF1EHLSi3DKGBuDfcWeGAlNTNSccOKaov4T7aP-eep3oYtjoEFeNk2-OVp_quUj1gB6Y2Yi9_gRkfPEtehZtufuoFcP7FLfEibjLx_co1ymk7-t3skZ6dNFg-yFREdnMPAf1WmF-BKo8SeT3knfs_H2FoKSQCLaYvAiXwOOGoDvotFOOtY92TeWvKtOBfQH6mJO1ox_p0O-GnIYdfFXX6gMf17LuUBrqkuL_ResXku2mW_UkkMoFx"/>
<div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
<div class="absolute bottom-4 left-4">
<span class="bg-primary text-xs font-bold text-white px-2 py-1 rounded">Outdoor</span>
</div>
</div>
<h4 class="text-lg font-bold">Rental Camping Adventure</h4>
<p class="text-slate-600 dark:text-slate-400 text-sm">Manajemen booking lebih tertata, mengurangi risiko kehilangan alat hingga 60%.</p>
</div>
<!-- Case 3 -->
<div class="group cursor-pointer">
<div class="aspect-[4/3] rounded-xl overflow-hidden mb-4 relative">
<img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" data-alt="Fleet of rental cars parked in a lot" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB85f0pFcvnO6uajuesACYuX1NTnyGyH4bTvSERELMkGRKnWuvB05a3wu6jIzVeDGhvgoQ8rykUDNH9IllnTpaT-DnK7sGF9Rs_8S2qqt3shp_2HfF22LcwwGe0WcU5lyunVFY9EeNcEEs5_cmkXjIM21wneQX09AV8GpExvG4YKnqm2-fbJhVmUHFuB1NaXWag2uti0k26zKQk-4h8GvIrjpg6zSOfkqOvla7K3XsbD0e7ngX6B1pf-C9A9rO_B0C5MudEXUevkfYE"/>
<div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
<div class="absolute bottom-4 left-4">
<span class="bg-primary text-xs font-bold text-white px-2 py-1 rounded">Transportasi</span>
</div>
</div>
<h4 class="text-lg font-bold">Rental Mobil Elite</h4>
<p class="text-slate-600 dark:text-slate-400 text-sm">Skalabilitas armada yang cepat berkat sistem administrasi yang terotomatisasi.</p>
</div>
</div>
</div>
</section>
<!-- Pricing Section -->
<section class="py-20 bg-slate-50 dark:bg-slate-900/30">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="text-center mb-12">
<h2 class="text-3xl font-bold mb-4">Pilihan Paket Sesuai Kebutuhan</h2>
<div class="flex items-center justify-center gap-4 mt-6"><span class="text-sm font-medium" id="monthly-label">Bulanan</span>
<button class="relative w-12 h-6 bg-slate-200 rounded-full transition-colors" id="pricing-toggle" onclick="togglePricing()">
<span class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform" id="toggle-knob"></span>
</button>
<span class="text-sm font-medium text-slate-500" id="yearly-label">Tahunan <span class="text-green-500 font-bold">(Hemat 20%)</span></span>
<script>
    function togglePricing() {
        const knob = document.getElementById('toggle-knob');
        const toggle = document.getElementById('pricing-toggle');
        const isYearly = knob.classList.contains('translate-x-6');
        
        const basicPrice = document.getElementById('price-basic');
        const proPrice = document.getElementById('price-pro');
        
        if (isYearly) {
            knob.classList.remove('translate-x-6');
            toggle.classList.remove('bg-primary');
            toggle.classList.add('bg-slate-200');
            basicPrice.innerText = '19';
            proPrice.innerText = '49';
        } else {
            knob.classList.add('translate-x-6');
            toggle.classList.remove('bg-slate-200');
            toggle.classList.add('bg-primary');
            basicPrice.innerText = '15';
            proPrice.innerText = '39';
        }
    }
</script></div>
</div>
<div class="grid md:grid-cols-3 gap-8"><!-- Free -->
<div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col">
<h3 class="text-lg font-bold mb-2">Free</h3>
<div class="flex items-baseline gap-1 mb-2">
<span class="text-4xl font-black">$0</span>
<span class="text-slate-500">/bulan</span>
</div>
<p class="text-sm text-primary font-medium mb-6">Gratis selamanya</p>
<ul class="space-y-4 mb-8 flex-1">
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            Maksimal 15 order per bulan
        </li>
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            Dashboard Dasar
        </li>
</ul>
<button class="w-full py-3 rounded-lg border-2 border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Mulai Sekarang</button>
</div>
<!-- Basic -->
<div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border border-slate-200 dark:border-slate-700 flex flex-col">
<h3 class="text-lg font-bold mb-2">Basic</h3>
<div class="flex items-baseline gap-1 mb-2">
<span class="text-4xl font-black">$<span id="price-basic">19</span></span>
<span class="text-slate-500">/bulan</span>
</div>
<p class="text-sm text-primary font-medium mb-6">Gratis ujicoba 14 hari</p>
<ul class="space-y-4 mb-8 flex-1">
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            Order Tak Terbatas
        </li>
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            Hingga 100 Item Inventaris
        </li>
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            3 Akun Staff
        </li>
</ul>
<button class="w-full py-3 rounded-lg border-2 border-slate-200 dark:border-slate-700 font-bold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">Pilih Paket</button>
</div>
<!-- Pro -->
<div class="bg-white dark:bg-slate-800 p-8 rounded-2xl border-2 border-primary shadow-xl relative flex flex-col scale-105 z-10">
<div class="absolute -top-4 left-1/2 -translate-x-1/2 bg-primary text-white text-xs font-bold px-4 py-1 rounded-full uppercase tracking-wider">Terpopuler</div>
<h3 class="text-lg font-bold mb-2">Pro</h3>
<div class="flex items-baseline gap-1 mb-2">
<span class="text-4xl font-black text-primary">$<span id="price-pro">49</span></span>
<span class="text-slate-500">/bulan</span>
</div>
<p class="text-sm text-primary font-medium mb-6">Gratis ujicoba 14 hari</p>
<ul class="space-y-4 mb-8 flex-1">
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            Fitur Enterprise Lengkap
        </li>
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            Inventaris Tak Terbatas
        </li>
<li class="flex items-center gap-2 text-sm">
<span class="material-symbols-outlined text-primary text-xl">check_circle</span>
            Staff Tak Terbatas
        </li>
</ul>
<button class="w-full py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90 transition-all shadow-lg shadow-primary/30">Mulai Uji Coba</button>
</div></div>
</div>
</section>
<!-- Testimonials Section -->
<section class="py-20">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<h2 class="text-3xl font-bold mb-12 text-center">Apa Kata Mereka?</h2><div class="flex flex-wrap justify-center items-center gap-8 md:gap-16 mb-16 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-3xl">bolt</span>
<span class="font-black text-xl tracking-tighter">TechRent</span>
</div>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-3xl">forest</span>
<span class="font-black text-xl tracking-tighter">OutdoorGo</span>
</div>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-3xl">directions_car</span>
<span class="font-black text-xl tracking-tighter">AutoLease</span>
</div>
<div class="flex items-center gap-2">
<span class="material-symbols-outlined text-3xl">home_repair_service</span>
<span class="font-black text-xl tracking-tighter">CamHire</span>
</div>
</div>
<div class="grid md:grid-cols-3 gap-8">
<!-- Testimonial 1 -->
<div class="bg-slate-50 dark:bg-slate-800/50 p-8 rounded-2xl italic relative">
<span class="material-symbols-outlined text-primary/20 text-6xl absolute top-4 left-4">format_quote</span>
<p class="relative z-10 text-slate-700 dark:text-slate-300 mb-6">
                        "Zewalo benar-benar mengubah cara kami mengelola 200+ unit kamera. Sekarang semuanya tercatat rapi dan otomatis."
                    </p>
<div class="flex items-center gap-4">
<div class="w-12 h-12 rounded-full overflow-hidden bg-slate-200">
<img class="w-full h-full object-cover" data-alt="Profile photo of a male business owner" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBE1Nzhx9YN_55O8qfG8t5GQT1-M4EUlDlIBHC7xv5kK9d4uMJYJC4k_EMApJi6OECCyV0sDI3b-c3o9QtkDQAo_Qil9SE2UxPJfY5XLinqw4iP96paFSUHk_Pxv3F-GIX0_9DdPG5zbwxnch18uH048GM-du1Lx3YcCwVtuanXf9WI7rA1JPIpUDcIRI-6PlvkWViXNA2Z7sdc8HyQVt3d0LcYfg6YY5aSilT_Jn07Ay2q3WH6UUu_vywxToGxtHBySPmLszDkW_wT"/>
</div>
<div>
<p class="font-bold text-sm">Budi Santoso</p>
<p class="text-xs text-slate-500">Owner, Rental Kamera Pro</p>
</div>
</div>
</div>
<!-- Testimonial 2 -->
<div class="bg-slate-50 dark:bg-slate-800/50 p-8 rounded-2xl italic relative">
<span class="material-symbols-outlined text-primary/20 text-6xl absolute top-4 left-4">format_quote</span>
<p class="relative z-10 text-slate-700 dark:text-slate-300 mb-6">
                        "Portal booking online-nya sangat membantu pelanggan kami memesan alat camping tanpa harus chat admin berkali-kali."
                    </p>
<div class="flex items-center gap-4">
<div class="w-12 h-12 rounded-full overflow-hidden bg-slate-200">
<img class="w-full h-full object-cover" data-alt="Profile photo of a female entrepreneur" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDCGpNk0L7qVqN6iOfVTYjEH1FTQuMf0_iD_-uWk2dhW5x1oQ9CRLPbH67-joeKC4oC2jCUaj-49CiU1ruNI2r5oI9ewHqOEshlV_imb2lEVgxNSGYNui6ZAhKv_tJVuS3fscTU3Wwt1Zt32MFsNLRDd69QuZWS3BfR-s5pDZ3_UQv5Q1h_m4KhZKEHcFuJRqehognl78vwumoikzrv87TQaMHy8FPo9-TZIjy2n9QMTHKGdrATL7fhD1gFNrZNyqokp1IdYIokOJdJ"/>
</div>
<div>
<p class="font-bold text-sm">Siti Aminah</p>
<p class="text-xs text-slate-500">Founder, Adventure Camp</p>
</div>
</div>
</div>
<!-- Testimonial 3 -->
<div class="bg-slate-50 dark:bg-slate-800/50 p-8 rounded-2xl italic relative">
<span class="material-symbols-outlined text-primary/20 text-6xl absolute top-4 left-4">format_quote</span>
<p class="relative z-10 text-slate-700 dark:text-slate-300 mb-6">
                        "Fitur laporan keuangannya sangat akurat. Saya bisa melihat profit bersih secara real-time dari genggaman tangan."
                    </p>
<div class="flex items-center gap-4">
<div class="w-12 h-12 rounded-full overflow-hidden bg-slate-200">
<img class="w-full h-full object-cover" data-alt="Profile photo of a male fleet manager" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAkzJymxCW1iJUbw7KHpjLXMiBPo9BSjMn4KY6gVesCHH6_31qZ__w0NKg1I0JRndkecHizf2XLVDmRyglkwqvDMLMYkpnoTmOcrl81JxAq6kI1CDCNU5jHpxguTYEvNo6Y0nrz1hGj2LzfMFcvemxWjjmFUbHenPmCcP6KpoyYV2CVKcyBcSy-HKWTdyj9MK8lakZWhV0Z4ivDsh-1pfQTViNblzjXk6w-8qq7K04UoefiJ2-6mW37wv0esmM5B9u71G2fU8ATcu-3"/>
</div>
<div>
<p class="font-bold text-sm">Andi Wijaya</p>
<p class="text-xs text-slate-500">Manager, Elite Car Rental</p>
</div>
</div>
</div>
</div>
</div>
</section>
<!-- Final CTA Section -->
<section class="py-20">
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="bg-primary rounded-3xl p-10 sm:p-16 text-center text-white relative overflow-hidden">
<div class="relative z-10">
<h2 class="text-3xl sm:text-4xl font-black mb-6">Siap Mengembangkan Bisnis Anda?</h2>
<p class="text-blue-100 text-lg mb-10 max-w-2xl mx-auto">
                        Bergabunglah dengan ratusan pengusaha rental lainnya yang telah mendigitalisasi bisnis mereka bersama Zewalo.
                    </p>
<button class="bg-white text-primary hover:bg-slate-100 text-lg font-bold px-10 py-4 rounded-xl transition-all shadow-xl">
                        Mulai Sekarang
                    </button>
<p class="mt-6 text-sm text-blue-200">Gratis 14 hari • Tidak butuh kartu kredit</p>
</div>
<!-- Abstract pattern background for CTA -->
<div class="absolute inset-0 opacity-10 pointer-events-none">
<svg height="100%" preserveaspectratio="none" viewbox="0 0 100 100" width="100%">
<path d="M0 0 L100 0 L100 100 L0 100 Z" fill="url(#grad1)"></path>
<defs>
<lineargradient id="grad1" x1="0%" x2="100%" y1="0%" y2="100%">
<stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:0.2"></stop>
<stop offset="100%" style="stop-color:rgb(255,255,255);stop-opacity:0"></stop>
</lineargradient>
</defs>
</svg>
</div>
</div>
</div>
</section>
<!-- Footer -->
<footer class="bg-slate-50 border-t border-slate-200 dark:border-slate-800 py-16">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-12">
<div class="col-span-2 lg:col-span-2">
<div class="flex items-center gap-2 mb-6">
<div class="text-primary">
<svg class="h-6 w-6" fill="currentColor" viewbox="0 0 48 48">
<path d="M36.7273 44C33.9891 44 31.6043 39.8386 30.3636 33.69C29.123 39.8386 26.7382 44 24 44C21.2618 44 18.877 39.8386 17.6364 33.69C16.3957 39.8386 14.0109 44 11.2727 44C7.25611 44 4 35.0457 4 24C4 12.9543 7.25611 4 11.2727 4C14.0109 4 16.3957 8.16144 17.6364 14.31C18.877 8.16144 21.2618 4 24 4C26.7382 4 29.123 8.16144 30.3636 14.31C31.6043 8.16144 33.9891 4 36.7273 4C40.7439 4 44 12.9543 44 24C44 35.0457 40.7439 44 36.7273 44Z"></path>
</svg>
</div>
<span class="text-xl font-bold tracking-tight">Zewalo</span>
</div>
<p class="text-slate-500 text-sm leading-relaxed max-w-xs mb-6">
                        Solusi modern untuk manajemen bisnis rental Anda. Efisien, terintegrasi, dan mudah digunakan.
                    </p>
<div class="flex gap-4">
<a class="text-slate-400 hover:text-primary transition-colors" href="#">
<span class="material-symbols-outlined">public</span>
</a>
<a class="text-slate-400 hover:text-primary transition-colors" href="#">
<span class="material-symbols-outlined">alternate_email</span>
</a>
<a class="text-slate-400 hover:text-primary transition-colors" href="#">
<span class="material-symbols-outlined">share</span>
</a>
</div>
</div>
<div>
<h4 class="font-bold text-sm mb-6 uppercase tracking-wider">Perusahaan</h4>
<ul class="space-y-4">
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Tentang Kami</a></li>
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Karir</a></li>
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Blog</a></li>
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Kontak</a></li>
</ul>
</div>
<div>
<h4 class="font-bold text-sm mb-6 uppercase tracking-wider">Legal</h4>
<ul class="space-y-4">
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Privasi</a></li>
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Ketentuan</a></li>
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Keamanan</a></li>
</ul>
</div>
<div>
<h4 class="font-bold text-sm mb-6 uppercase tracking-wider">Dukungan</h4>
<ul class="space-y-4">
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Pusat Bantuan</a></li>
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Dokumentasi</a></li>
<li><a class="text-sm text-slate-500 hover:text-primary" href="#">Status Sistem</a></li>
</ul>
</div>
</div>
<div class="mt-16 pt-8 border-t border-slate-200 dark:border-slate-800 text-center">
<p class="text-sm text-slate-500">© 2024 Zewalo.com. Seluruh hak cipta dilindungi.</p>
</div>
</div>
</footer>
</body></html>
