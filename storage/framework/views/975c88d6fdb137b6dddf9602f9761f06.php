<div class="min-h-screen flex items-center justify-center p-4 font-[Inter]"
     style="background-color: #f6f8f8;">

    
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full blur-3xl" style="background: rgba(20,184,166,0.06);"></div>
        <div class="absolute bottom-0 right-0 w-80 h-80 rounded-full blur-3xl" style="background: rgba(20,184,166,0.1);"></div>
        <div class="absolute inset-0"
             style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 50% 50% at 50% 50%, #000 70%, transparent 100%); -webkit-mask-image: radial-gradient(ellipse 50% 50% at 50% 50%, #000 70%, transparent 100%);">
        </div>
    </div>

    <div class="w-full max-w-xl"
         x-data="{}"
         x-init="$nextTick(() => { $el.classList.add('opacity-100', 'translate-y-0'); })"
         class="opacity-0 translate-y-4 transition-all duration-700 ease-out">

        
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl text-white" style="background-color: #14B8A6;">
                    <span class="material-symbols-outlined text-2xl">storefront</span>
                </div>
                <span class="text-2xl font-bold tracking-tight text-slate-900">Zewalo</span>
            </div>
        </div>

        
        <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-slate-200">

            
            
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentStep === 1): ?>
            <div x-data="{ show: false }" x-init="$nextTick(() => show = true)">
                <div x-show="show"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">

                    
                    <div class="p-8 pb-0">
                        <div class="flex flex-col gap-3">
                            <div class="flex gap-6 justify-between items-center">
                                <p class="text-slate-900 text-sm font-semibold uppercase tracking-wider">Langkah 1 dari 3</p>
                                <p class="text-sm font-bold" style="color: #14B8A6;">33%</p>
                            </div>
                            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700 ease-out" style="width: 33%; background-color: #14B8A6;"></div>
                            </div>
                            <p class="text-slate-500 text-xs font-medium">Detail Pengguna</p>
                        </div>
                    </div>

                    
                    <div class="px-8 pt-8 pb-4">
                        <h1 class="text-slate-900 tracking-tight text-3xl font-extrabold leading-tight">Mulai Langkah Anda</h1>
                        <p class="text-slate-600 text-base mt-2">Daftar akun admin untuk mengelola bisnis rental Anda.</p>
                    </div>

                    
                    <form wire:submit="nextStep" class="px-8 pb-10 space-y-5">
                        <div class="space-y-4">
                            
                            <div class="block">
                                <span class="text-slate-700 text-sm font-semibold mb-1.5 block">Nama Lengkap</span>
                                <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6] <?php echo e($errors->has('admin_name') ? 'border-red-400 focus-within:ring-red-200 focus-within:border-red-400' : ''); ?>">
                                    <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                        <span class="material-symbols-outlined text-xl">person</span>
                                    </div>
                                    <input wire:model="admin_name" type="text" placeholder="Masukkan nama lengkap Anda"
                                           class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 border-none">
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['admin_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            
                            <div class="block">
                                <span class="text-slate-700 text-sm font-semibold mb-1.5 block">Email Bisnis</span>
                                <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6] <?php echo e($errors->has('admin_email') ? 'border-red-400 focus-within:ring-red-200 focus-within:border-red-400' : ''); ?>">
                                    <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                        <span class="material-symbols-outlined text-xl">mail</span>
                                    </div>
                                    <input wire:model="admin_email" type="email" placeholder="contoh@email.com"
                                           class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 border-none">
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['admin_email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="block">
                                    <span class="text-slate-700 text-sm font-semibold mb-1.5 block">Kata Sandi</span>
                                    <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6] <?php echo e($errors->has('password') ? 'border-red-400 focus-within:ring-red-200 focus-within:border-red-400' : ''); ?>">
                                        <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                            <span class="material-symbols-outlined text-xl">lock</span>
                                        </div>
                                        <input wire:model="password" type="password" placeholder="••••••••"
                                               class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 border-none">
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="block">
                                    <span class="text-slate-700 text-sm font-semibold mb-1.5 block">Konfirmasi Sandi</span>
                                    <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6]">
                                        <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                            <span class="material-symbols-outlined text-xl">lock_reset</span>
                                        </div>
                                        <input wire:model="password_confirmation" type="password" placeholder="••••••••"
                                               class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 border-none">
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="pt-4">
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-75 cursor-wait"
                                    class="w-full text-white font-bold py-4 px-6 rounded-lg shadow-lg transition-all flex items-center justify-center gap-2 group hover:opacity-90"
                                    style="background-color: #14B8A6; box-shadow: 0 10px 15px -3px rgba(20,184,166,0.2);">
                                <span wire:loading.remove>Lanjutkan ke Langkah 2</span>
                                <span wire:loading class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Memvalidasi...
                                </span>
                                <span wire:loading.remove class="material-symbols-outlined transition-transform group-hover:translate-x-1">arrow_forward</span>
                            </button>
                            <p class="text-center text-slate-500 text-sm mt-6">
                                Sudah memiliki akun?
                                <a href="/central" class="font-semibold hover:underline" style="color: #14B8A6;">Masuk di sini</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentStep === 2): ?>
            <div x-data="{ show: false }" x-init="$nextTick(() => show = true)">
                <div x-show="show"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-x-8"
                     x-transition:enter-end="opacity-100 translate-x-0">

                    
                    <div class="p-8 pb-0">
                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-end">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider" style="color: #14B8A6;">Langkah 2 dari 3</span>
                                    <p class="text-slate-500 text-sm font-medium">Informasi Bisnis</p>
                                </div>
                                <p class="text-slate-900 text-sm font-bold">66%</p>
                            </div>
                            <div class="w-full h-2 rounded-full bg-slate-100">
                                <div class="h-full rounded-full transition-all duration-700 ease-out" style="width: 66%; background-color: #14B8A6;"></div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="p-8">
                        <header class="mb-8">
                            <h2 class="text-slate-900 text-2xl font-bold leading-tight mb-2">Detail Bisnis & Paket</h2>
                            <p class="text-slate-500 text-base">Lengkapi informasi bisnis dan pilih paket langganan yang sesuai.</p>
                        </header>

                        <form wire:submit="nextStep" class="space-y-6">
                            
                            <div class="flex flex-col gap-2">
                                <span class="text-slate-700 text-sm font-semibold block">Nama Bisnis</span>
                                <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6] <?php echo e($errors->has('store_name') ? 'border-red-400 focus-within:ring-red-200 focus-within:border-red-400' : ''); ?>">
                                    <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                        <span class="material-symbols-outlined text-xl">storefront</span>
                                    </div>
                                    <input wire:model.live.debounce.500ms="store_name" type="text" placeholder="Contoh: Kamera Pro Rental"
                                           class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 border-none">
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['store_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            
                            <div class="flex flex-col gap-2">
                                <span class="text-slate-700 text-sm font-semibold block">Domain Toko</span>
                                <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6] <?php echo e($errors->has('subdomain') ? 'border-red-400 focus-within:ring-red-200 focus-within:border-red-400' : ''); ?>">
                                    <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                        <span class="material-symbols-outlined text-xl">language</span>
                                    </div>
                                    <input wire:model.live.debounce.500ms="subdomain" type="text" placeholder="nama-toko"
                                           class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 border-none">
                                    <div class="flex items-center px-4 bg-slate-100 border-l border-slate-200 text-slate-500 font-medium text-sm">
                                        .<?php echo e(config('app.domain', 'zewalo.com')); ?>

                                    </div>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['subdomain'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="text-sm text-red-500"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subdomain && !$errors->has('subdomain')): ?>
                                    <p class="text-xs font-medium" style="color: #14B8A6;">
                                        ✓ <?php echo e($subdomain); ?>.<?php echo e(config('app.domain', 'zewalo.com')); ?> tersedia
                                    </p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <p class="text-xs text-slate-400">Gunakan huruf kecil, angka, dan tanda hubung saja.</p>
                            </div>

                            
                            <div class="flex flex-col gap-2">
                                <span class="text-slate-700 text-sm font-semibold block">Kategori Bisnis</span>
                                <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6] <?php echo e($errors->has('business_category') ? 'border-red-400 focus-within:ring-red-200 focus-within:border-red-400' : ''); ?>">
                                    <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                        <span class="material-symbols-outlined text-xl">category</span>
                                    </div>
                                    <select wire:model="business_category"
                                            class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 focus:outline-none focus:ring-0 border-none">
                                        <option value="" disabled selected>Pilih kategori rental</option>
                                        <option value="photography">Fotografi & Videografi</option>
                                        <option value="automotive">Kendaraan & Otomotif</option>
                                        <option value="camping">Peralatan Camping</option>
                                        <option value="electronics">Elektronik & Gadget</option>
                                        <option value="wedding">Peralatan Pernikahan</option>
                                        <option value="sports">Peralatan Olahraga</option>
                                        <option value="music">Alat Musik & Sound System</option>
                                        <option value="other">Lainnya</option>
                                    </select>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['business_category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-500"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            
                            <div class="mt-6 space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div>
                                        <p class="text-slate-700 text-sm font-semibold">Pilih Paket</p>
                                        <p class="text-xs text-slate-500">Anda bisa upgrade atau downgrade kapan saja dari dashboard.</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                        Rekomendasi: Trial 14 hari dulu
                                    </span>
                                </div>

                                <?php
                                    $plansCollection = collect($plans ?? []);
                                    $freePlan = $plansCollection->firstWhere('slug', 'free');
                                    $basicPlan = $plansCollection->firstWhere('slug', 'basic');
                                    $proPlan = $plansCollection->firstWhere('slug', 'pro');

                                    $formatPrice = function ($plan) {
                                        if (! $plan) return 'Rp 0';
                                        return 'Rp ' . number_format((float) ($plan['price_monthly'] ?? 0), 0, ',', '.');
                                    };
                                ?>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    
                                    <?php $isSelected = $selected_plan_slug === 'free'; ?>
                                    <button
                                        type="button"
                                        wire:click="$set('selected_plan_slug', 'free')"
                                        class="group flex flex-col items-start rounded-xl border p-4 text-left transition-all hover:shadow-md hover:border-emerald-400 <?php echo e($isSelected ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white'); ?>"
                                    >
                                        <div class="flex items-center justify-between w-full mb-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide <?php echo e($isSelected ? 'text-emerald-700' : 'text-slate-500'); ?>">Paket Free</span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isSelected): ?>
                                                <span class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[11px] font-semibold text-emerald-700 shadow-sm">
                                                    <span class="material-symbols-outlined text-xs mr-1">check</span>Terpilih
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center rounded-full bg-slate-50 px-2 py-0.5 text-[11px] font-medium text-slate-500">
                                                    Tanpa Trial
                                                </span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                        <p class="text-lg font-bold text-slate-900 mb-1"><?php echo e($formatPrice($freePlan)); ?><span class="text-xs font-normal text-slate-500"> /bulan</span></p>
                                        <p class="text-xs text-slate-500 mb-3">Langsung aktif tanpa masa trial dengan limit transaksi & produk.</p>
                                        <ul class="space-y-1 text-[11px] text-slate-600">
                                            <li class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-emerald-500 text-xs">check_small</span>
                                                Cocok untuk coba cepat dengan skala kecil
                                            </li>
                                            <li class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-emerald-500 text-xs">check_small</span>
                                                Limit transaksi per bulan (dapat diatur admin)
                                            </li>
                                        </ul>
                                    </button>

                                    
                                    <?php $isSelected = $selected_plan_slug === 'basic'; ?>
                                    <button
                                        type="button"
                                        wire:click="$set('selected_plan_slug', 'basic')"
                                        class="relative group flex flex-col items-start rounded-xl border p-4 text-left transition-all hover:shadow-md hover:border-emerald-400 <?php echo e($isSelected ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white'); ?>"
                                    >
                                        <span class="absolute -top-2 right-3 inline-flex items-center rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-semibold text-white shadow">
                                            Rekomendasi
                                        </span>
                                        <div class="flex items-center justify-between w-full mb-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide <?php echo e($isSelected ? 'text-emerald-700' : 'text-slate-500'); ?>">Basic</span>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                                Trial 14 hari
                                            </span>
                                        </div>
                                        <p class="text-lg font-bold text-slate-900 mb-1"><?php echo e($formatPrice($basicPlan)); ?><span class="text-xs font-normal text-slate-500"> /bulan</span></p>
                                        <p class="text-xs text-slate-500 mb-3">Mulai dengan trial 14 hari, cocok untuk bisnis yang mulai berkembang.</p>
                                        <ul class="space-y-1 text-[11px] text-slate-600">
                                            <li class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-emerald-500 text-xs">check_small</span>
                                                Fitur standar untuk operasional harian
                                            </li>
                                            <li class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-emerald-500 text-xs">check_small</span>
                                                Trial wajib 14 hari sebelum mulai berlangganan
                                            </li>
                                        </ul>
                                    </button>

                                    
                                    <?php $isSelected = $selected_plan_slug === 'pro'; ?>
                                    <button
                                        type="button"
                                        wire:click="$set('selected_plan_slug', 'pro')"
                                        class="group flex flex-col items-start rounded-xl border p-4 text-left transition-all hover:shadow-md hover:border-emerald-400 <?php echo e($isSelected ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white'); ?>"
                                    >
                                        <div class="flex items-center justify-between w-full mb-2">
                                            <span class="text-xs font-semibold uppercase tracking-wide <?php echo e($isSelected ? 'text-emerald-700' : 'text-slate-500'); ?>">Pro</span>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700">
                                                Trial 14 hari
                                            </span>
                                        </div>
                                        <p class="text-lg font-bold text-slate-900 mb-1"><?php echo e($formatPrice($proPlan)); ?><span class="text-xs font-normal text-slate-500"> /bulan</span></p>
                                        <p class="text-xs text-slate-500 mb-3">Untuk bisnis rental serius dengan kebutuhan fitur & kapasitas lebih besar.</p>
                                        <ul class="space-y-1 text-[11px] text-slate-600">
                                            <li class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-emerald-500 text-xs">check_small</span>
                                                Batas transaksi lebih tinggi / bisa unlimited
                                            </li>
                                            <li class="flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-emerald-500 text-xs">check_small</span>
                                                Prioritas support & fitur lanjutan
                                            </li>
                                        </ul>
                                    </button>
                                </div>

                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['selected_plan_slug'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <p class="text-sm text-red-500"><?php echo e($message); ?></p>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            
                            <div class="pt-4 flex gap-3">
                                <button type="button"
                                        wire:click="previousStep"
                                        class="flex-1 px-6 py-3 border border-slate-200 text-slate-600 font-semibold rounded-lg hover:bg-slate-50 transition-colors flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-xl">arrow_back</span>
                                    Kembali
                                </button>
                                <button type="submit"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-75 cursor-wait"
                                        class="flex-[2] px-6 py-3 text-white font-semibold rounded-lg shadow-lg transition-all flex items-center justify-center gap-2 hover:opacity-90"
                                        style="background-color: #14B8A6; box-shadow: 0 10px 15px -3px rgba(20,184,166,0.2);">
                                    <span wire:loading.remove>Lanjutkan</span>
                                    <span wire:loading class="flex items-center gap-2">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                    <span wire:loading.remove class="material-symbols-outlined text-xl">arrow_forward</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    
                    <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 text-center">
                        <p class="text-xs text-slate-400">Punya kendala? <a href="#" class="hover:underline" style="color: #14B8A6;">Hubungi Tim Support</a></p>
                    </div>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentStep === 3): ?>
            
            <div class="p-0" <?php if(!in_array($provisioningStatus, ['ready', 'failed'])): ?> wire:poll.2000ms="checkProvisioningStatus" <?php endif; ?>>

                <div class="flex flex-col items-center text-center px-8 pt-10 pb-6"
                     x-data="{ show: false }" x-init="$nextTick(() => show = true)"
                     x-show="show"
                     x-transition:enter="transition ease-out duration-600"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0">

                    
                    <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl text-white shadow-lg"
                         style="background-color: <?php echo e($provisioningStatus === 'failed' ? '#ef4444' : '#14B8A6'); ?>; box-shadow: 0 10px 25px -5px <?php echo e($provisioningStatus === 'failed' ? 'rgba(239,68,68,0.3)' : 'rgba(20,184,166,0.3)'); ?>;">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provisioningStatus === 'failed'): ?>
                            <span class="material-symbols-outlined text-3xl">error</span>
                        <?php elseif($provisioningStatus === 'ready'): ?>
                            <span class="material-symbols-outlined text-3xl">check_circle</span>
                        <?php else: ?>
                            <span class="material-symbols-outlined text-3xl animate-pulse">rocket_launch</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <h1 class="text-slate-900 tracking-tight text-[28px] font-bold leading-tight pb-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provisioningStatus === 'failed'): ?>
                            Gagal Membuat Tenant
                        <?php elseif($provisioningStatus === 'ready'): ?>
                            Tenant Berhasil Dibuat!
                        <?php else: ?>
                            Menyiapkan Toko Anda
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </h1>
                    <p class="text-slate-500 text-sm font-normal leading-relaxed max-w-sm">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provisioningStatus === 'failed'): ?>
                            Terjadi kesalahan saat membuat tenant. Silakan coba lagi.
                        <?php elseif($provisioningStatus === 'ready'): ?>
                            Toko Anda sudah siap digunakan!
                        <?php else: ?>
                            Mohon tunggu sebentar sementara kami membangun sistem rental Anda.
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                </div>

                
                <div class="mx-8 mb-6 flex flex-col gap-5 rounded-2xl border border-slate-200 bg-slate-50/50 p-6">
                    
                    <div class="flex flex-col gap-3">
                        <div class="flex justify-between items-end">
                            <p class="text-slate-900 text-sm font-semibold">Progres Instalasi</p>
                            <p class="text-sm font-bold" style="color: #14B8A6;"><?php echo e($provisioningProgress); ?>%</p>
                        </div>
                        <div class="h-2.5 w-full rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 ease-out"
                                 style="background-color: <?php echo e($provisioningStatus === 'failed' ? '#ef4444' : '#14B8A6'); ?>; width: <?php echo e($provisioningProgress); ?>%">
                            </div>
                        </div>
                    </div>

                    
                    <div class="flex items-center gap-3 py-3">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full transition-all duration-500
                            <?php echo e($provisioningStatus === 'ready' ? 'bg-[#14B8A6]/10 text-[#14B8A6]' : ($provisioningStatus === 'failed' ? 'bg-red-100 text-red-500' : 'bg-[#14B8A6] text-white ring-4 ring-[#14B8A6]/10')); ?>">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provisioningStatus === 'ready'): ?>
                                <span class="material-symbols-outlined text-xl font-bold">check</span>
                            <?php elseif($provisioningStatus === 'failed'): ?>
                                <span class="material-symbols-outlined text-xl">close</span>
                            <?php else: ?>
                                <span class="material-symbols-outlined text-xl animate-spin">sync</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                        <div class="flex flex-col text-left">
                            <p class="text-sm font-semibold text-slate-900"><?php echo e($provisioningStep ?: 'Memulai...'); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provisioningStatus === 'ready'): ?>
                                <p class="text-xs text-slate-500">Selesai</p>
                            <?php elseif($provisioningStatus === 'failed'): ?>
                                <p class="text-xs text-red-500"><?php echo e(Str::limit($provisioningError, 80)); ?></p>
                            <?php else: ?>
                                <p class="text-xs text-[#14B8A6] font-medium">Sedang berjalan...</p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                </div>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provisioningStatus === 'ready'): ?>
                <div class="mx-8 mb-6" x-data="{ show: false }" x-init="$nextTick(() => show = true)"
                     x-show="show"
                     x-transition:enter="transition ease-out duration-500"
                     x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100">
                    <div class="flex flex-col items-center justify-between gap-4 rounded-2xl border p-6 md:flex-row md:text-left"
                         style="border-color: rgba(20,184,166,0.2); background-color: rgba(20,184,166,0.05);">
                        <div class="flex flex-col gap-1 items-center md:items-start">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined" style="color: #14B8A6;">verified</span>
                                <p class="text-slate-900 text-base font-bold leading-tight">Pendaftaran Berhasil!</p>
                            </div>
                            <p class="text-slate-600 text-sm font-normal">Toko Anda sudah siap di alamat:</p>
                        </div>
                        <div class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 shadow-sm border border-slate-200">
                            <span class="text-sm font-semibold" style="color: #14B8A6;"><?php echo e($tenantDomain); ?></span>
                            <button x-data
                                    x-on:click="navigator.clipboard.writeText('http://<?php echo e($tenantDomain); ?>')"
                                    class="material-symbols-outlined text-slate-400 text-sm hover:text-[#14B8A6] transition-colors cursor-pointer">
                                content_copy
                            </button>
                        </div>
                    </div>

                    
                    <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                        <p class="text-sm text-slate-600 text-center">
                            Login ke <strong class="font-semibold" style="color: #14B8A6;"><?php echo e($tenantDomain); ?>/admin</strong>
                            dengan email dan password yang telah Anda daftarkan.
                        </p>
                    </div>

                    
                    <div class="mt-4">
                        <a href="http://<?php echo e($tenantDomain); ?>/admin"
                           class="block w-full text-center py-3 px-6 text-white font-semibold rounded-lg shadow-lg transition-all hover:opacity-90"
                           style="background-color: #14B8A6; box-shadow: 0 10px 15px -3px rgba(20,184,166,0.2);">
                            Masuk ke Dashboard
                            <span class="material-symbols-outlined align-middle ml-1">arrow_forward</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($provisioningStatus === 'failed'): ?>
                <div class="mx-8 mb-6">
                    <div class="p-4 rounded-xl bg-red-50 border border-red-100 mb-4">
                        <p class="text-sm text-red-600 text-center">
                            <strong>Error:</strong> <?php echo e($provisioningError); ?>

                        </p>
                    </div>
                    <button wire:click="retryRegistration"
                            wire:loading.attr="disabled"
                            class="w-full py-3 px-6 bg-red-500 hover:bg-red-600 text-white font-semibold rounded-lg shadow-lg transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined">refresh</span>
                        Coba Lagi
                    </button>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                
                <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 text-center">
                    <p class="text-slate-400 text-xs">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($provisioningStatus, ['queued', 'creating_db', 'creating_admin'])): ?>
                            Jangan tutup halaman ini sampai proses selesai.
                        <?php elseif($provisioningStatus === 'ready'): ?>
                            Kami akan mengirimkan email konfirmasi setelah sistem siap.
                        <?php else: ?>
                            Hubungi support jika masalah berlanjut.
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

</div>

        
        <p class="text-center text-xs text-slate-400 mt-6">&copy; <?php echo e(date('Y')); ?> Zewalo. All rights reserved.</p>
    </div>
</div>
<?php /**PATH /var/www/resources/views/livewire/register-tenant.blade.php ENDPATH**/ ?>