@extends('layouts.frontend')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-8">Welcome, {{ $customer->name }}!</h1>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-primary-100 rounded-full mr-4">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Active Rentals</p>
                    <p class="text-2xl font-bold">{{ $activeRentals->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-full mr-4">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Completed</p>
                    <p class="text-2xl font-bold">{{ $pastRentals->where('status', 'completed')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-full mr-4">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Cart Items</p>
                    <p class="text-2xl font-bold">{{ $cartCount }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Rentals -->
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="p-6 border-b">
            <h2 class="text-lg font-semibold">Active Rentals</h2>
        </div>
        @if($activeRentals->count() > 0)
            <div class="divide-y">
                @foreach($activeRentals as $rental)
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <p class="font-semibold">{{ $rental->rental_code }}</p>
                            <p class="text-sm text-gray-600">{{ $rental->start_date->format('d M Y') }} - {{ $rental->end_date->format('d M Y') }}</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="px-3 py-1 rounded-full text-sm font-medium
                                @if($rental->status == 'pending') bg-yellow-100 text-yellow-800
                                @elseif($rental->status == 'active') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                            </span>
                            <a href="{{ route('customer.rental.detail', $rental->id) }}" class="text-primary-600 hover:underline">View Details</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-6 text-center text-gray-500">
                No active rentals.
            </div>
        @endif
    </div>

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <a href="{{ route('catalog.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="font-semibold mb-2">Browse Catalog</h3>
            <p class="text-sm text-gray-600">Find and rent equipment</p>
        </a>
        <a href="{{ route('customer.rentals') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="font-semibold mb-2">My Rentals</h3>
            <p class="text-sm text-gray-600">View all rental history</p>
        </a>
        <a href="{{ route('customer.profile') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <h3 class="font-semibold mb-2">Profile Settings</h3>
            <p class="text-sm text-gray-600">Update your information</p>
        </a>
    </div>
</div>
@endsection