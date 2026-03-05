@extends('layouts.frontend')

@section('title', 'Etalase Tidak Aktif')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center max-w-md mx-auto px-4">
        <div class="mb-6">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a2.25 2.25 0 0 1 1.5-2.121l6-2.25a2.25 2.25 0 0 1 1.5 0l6 2.25a2.25 2.25 0 0 1 1.5 2.121M3.75 9.349V4.5" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Etalase Toko Tidak Aktif</h1>
        <p class="text-gray-600 mb-6">Etalase toko ini sedang tidak tersedia. Silakan hubungi pengelola untuk informasi lebih lanjut.</p>
        <a href="/admin" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            Masuk ke Panel Admin
        </a>
    </div>
</div>
@endsection
