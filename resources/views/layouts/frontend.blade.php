<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Gearent') }} - @yield('title', 'Rental Equipment')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-primary-600">
                        @if(\App\Models\Setting::get('site_logo'))
                            <img src="{{ \Illuminate\Support\Facades\Storage::url(\App\Models\Setting::get('site_logo')) }}" alt="{{ \App\Models\Setting::get('site_name', 'Gearent') }}" class="h-10 w-auto">
                        @endif
                        @if(\App\Models\Setting::get('site_name_in_header', true))
                            <span>{{ \App\Models\Setting::get('site_name', 'Gearent') }}</span>
                        @endif
                    </a>
                    <div class="hidden sm:ml-10 sm:flex sm:space-x-8">
                        <a href="{{ route('home') }}" class="text-gray-900 hover:text-primary-600 px-3 py-2 text-sm font-medium">Home</a>
                        <a href="{{ route('catalog.index') }}" class="text-gray-900 hover:text-primary-600 px-3 py-2 text-sm font-medium">Catalog</a>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    @auth('customer')
                        <a href="{{ route('cart.index') }}" class="relative text-gray-600 hover:text-primary-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            @php $cartCount = auth('customer')->user()->carts()->count(); @endphp
                            @if($cartCount > 0)
                                <span class="absolute -top-2 -right-2 bg-primary-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">{{ $cartCount }}</span>
                            @endif
                        </a>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center text-sm font-medium text-gray-700 hover:text-primary-600">
                                {{ auth('customer')->user()->name }}
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="{{ route('customer.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                <a href="{{ route('customer.rentals') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Rentals</a>
                                <a href="{{ route('customer.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <form method="POST" action="{{ route('customer.logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('customer.login') }}" class="text-gray-600 hover:text-primary-600 text-sm font-medium">Login</a>
                        <a href="{{ route('customer.register') }}" class="bg-primary-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-primary-700">Register</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">{{ \App\Models\Setting::get('site_name', 'Gearent') }}</h3>
                    <p class="text-gray-400">{{ \App\Models\Setting::get('site_tagline', 'Your trusted equipment rental partner.') }}</p>
                    @if(\App\Models\Setting::get('site_address'))
                        <p class="text-gray-400 mt-4 text-sm font-light leading-relaxed whitespace-pre-line">{{ \App\Models\Setting::get('site_address') }}</p>
                    @endif
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white">Home</a></li>
                        <li><a href="{{ route('catalog.index') }}" class="hover:text-white">Catalog</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>Phone: {{ \App\Models\Setting::get('site_phone', '021-1234567') }}</li>
                        <li>Email: {{ \App\Models\Setting::get('site_email', 'info@gearent.com') }}</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Follow Us</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">Instagram</a>
                        <a href="#" class="text-gray-400 hover:text-white">Facebook</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                @if(\App\Models\Setting::get('site_copyright'))
                    {{ \App\Models\Setting::get('site_copyright') }}
                @else
                    &copy; {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'Gearent') }}. All rights reserved.
                @endif
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>