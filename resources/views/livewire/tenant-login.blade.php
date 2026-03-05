<div class="min-h-screen flex items-center justify-center p-4 font-[Inter]"
     style="background-color: #f6f8f8;">

    {{-- Decorative Background --}}
    <div class="fixed inset-0 -z-10 overflow-hidden pointer-events-none">
        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full blur-3xl" style="background: rgba(20,184,166,0.06);"></div>
        <div class="absolute bottom-0 right-0 w-80 h-80 rounded-full blur-3xl" style="background: rgba(20,184,166,0.1);"></div>
        <div class="absolute inset-0"
             style="background-image: radial-gradient(#e5e7eb 1px, transparent 1px); background-size: 24px 24px; mask-image: radial-gradient(ellipse 50% 50% at 50% 50%, #000 70%, transparent 100%); -webkit-mask-image: radial-gradient(ellipse 50% 50% at 50% 50%, #000 70%, transparent 100%);">
        </div>
    </div>

    <div class="w-full max-w-md"
         x-data="{}"
         x-init="$nextTick(() => { $el.classList.add('opacity-100', 'translate-y-0'); })"
         class="opacity-0 translate-y-4 transition-all duration-700 ease-out">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="/" class="inline-flex items-center gap-2.5">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl text-white" style="background-color: #14B8A6;">
                    <span class="material-symbols-outlined text-2xl">storefront</span>
                </div>
                <span class="text-2xl font-bold tracking-tight text-slate-900">Zewalo</span>
            </a>
        </div>

        {{-- Main Card --}}
        <div class="bg-white shadow-2xl rounded-xl overflow-hidden border border-slate-200">

            {{-- Header --}}
            <div class="px-8 pt-8 pb-6 border-b border-slate-100">
                <h1 class="text-slate-900 text-2xl font-extrabold tracking-tight">Masuk ke Toko Anda</h1>
                <p class="text-slate-500 text-sm mt-1.5">Masukkan subdomain atau email admin toko Anda.</p>
            </div>

            <div class="px-8 py-6">

                {{-- Search Form --}}
                @if (!$foundTenant || $isSuspended)
                <form wire:submit="searchTenant" class="space-y-4">
                    <div>
                        <label class="text-slate-700 text-sm font-semibold mb-1.5 block">Subdomain atau Email</label>
                        <div class="flex items-stretch rounded-lg shadow-sm border border-slate-200 bg-white overflow-hidden transition-all focus-within:ring-2 focus-within:ring-[#14B8A6]/20 focus-within:border-[#14B8A6] {{ $errors->has('input') ? 'border-red-400' : '' }}">
                            <div class="flex items-center justify-center w-12 bg-slate-50 border-r border-slate-200 text-slate-400 pointer-events-none">
                                <span class="material-symbols-outlined text-xl">search</span>
                            </div>
                            <input
                                wire:model="input"
                                type="text"
                                placeholder="nama-toko atau email@bisnis.com"
                                class="flex-1 w-full bg-transparent px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 border-none"
                                autofocus
                            >
                        </div>
                        @error('input')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-slate-400 mt-1.5">
                            Contoh: <span class="font-medium text-slate-500">kamera-pro</span> atau <span class="font-medium text-slate-500">admin@email.com</span>
                        </p>
                    </div>

                    {{-- Error Message --}}
                    @if ($errorMessage)
                        <div class="flex items-start gap-3 rounded-lg bg-red-50 border border-red-100 px-4 py-3">
                            <span class="material-symbols-outlined text-red-400 text-xl mt-0.5">error</span>
                            <p class="text-sm text-red-600">{{ $errorMessage }}</p>
                        </div>
                    @endif

                    {{-- Suspended Notice --}}
                    @if ($isSuspended && $foundTenant)
                        <div class="flex items-start gap-3 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3">
                            <span class="material-symbols-outlined text-amber-500 text-xl mt-0.5">warning</span>
                            <div>
                                <p class="text-sm font-semibold text-amber-800">Toko Dinonaktifkan</p>
                                <p class="text-xs text-amber-700 mt-0.5">
                                    Toko <strong>{{ $foundTenant['name'] }}</strong> sedang dinonaktifkan sementara.
                                    Hubungi <a href="mailto:support@zewalo.com" class="underline">support@zewalo.com</a> untuk bantuan.
                                </p>
                            </div>
                        </div>
                    @endif

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-wait"
                        class="w-full text-white font-bold py-3.5 px-6 rounded-lg shadow-lg transition-all flex items-center justify-center gap-2 hover:opacity-90"
                        style="background-color: #14B8A6; box-shadow: 0 10px 15px -3px rgba(20,184,166,0.2);">
                        <span wire:loading.remove class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-xl">search</span>
                            Cari Toko
                        </span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Mencari...
                        </span>
                    </button>
                </form>
                @endif

                {{-- Found Tenant - Confirm & Redirect --}}
                @if ($foundTenant && !$isSuspended)
                <div x-data="{ show: false }" x-init="$nextTick(() => show = true)"
                     x-show="show"
                     x-transition:enter="transition ease-out duration-400"
                     x-transition:enter-start="opacity-0 translate-y-3"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="space-y-5">

                    {{-- Tenant Preview Card --}}
                    <div class="flex items-center gap-4 rounded-xl border p-4"
                         style="border-color: rgba(20,184,166,0.3); background-color: rgba(20,184,166,0.04);">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl text-white text-lg font-bold"
                             style="background-color: #14B8A6;">
                            {{ strtoupper(substr($foundTenant['name'], 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-slate-900 truncate">{{ $foundTenant['name'] }}</p>
                            <p class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                <span class="material-symbols-outlined text-xs" style="color: #14B8A6;">link</span>
                                {{ $foundDomain }}
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                            {{ $foundTenant['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ $foundTenant['status'] === 'active' ? 'Aktif' : 'Trial' }}
                        </span>
                    </div>

                    {{-- Action Buttons --}}
                    <button
                        wire:click="redirectToTenant"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-wait"
                        class="w-full text-white font-bold py-3.5 px-6 rounded-lg shadow-lg transition-all flex items-center justify-center gap-2 hover:opacity-90"
                        style="background-color: #14B8A6; box-shadow: 0 10px 15px -3px rgba(20,184,166,0.2);">
                        <span wire:loading.remove class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-xl">login</span>
                            Masuk ke {{ $foundTenant['name'] }}
                        </span>
                        <span wire:loading class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Mengalihkan...
                        </span>
                    </button>

                    <button
                        wire:click="reset"
                        class="w-full py-2.5 text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-base">arrow_back</span>
                        Cari toko lain
                    </button>
                </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="px-8 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <a href="/register-tenant" class="text-xs font-semibold hover:underline" style="color: #14B8A6;">
                    Belum punya toko? Daftar gratis
                </a>
                <a href="/central" class="text-xs text-slate-400 hover:text-slate-600 transition-colors">
                    Login Admin
                </a>
            </div>
        </div>

        {{-- Copyright --}}
        <p class="text-center text-xs text-slate-400 mt-6">&copy; {{ date('Y') }} Zewalo. All rights reserved.</p>
    </div>
</div>
