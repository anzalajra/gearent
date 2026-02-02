@extends('layouts.frontend')

@section('title', 'Shopping Cart')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-8">Shopping Cart</h1>

    <!-- Verification Warning -->
    @if(!$canCheckout)
        <div class="mb-6 p-4 bg-red-50 border border-red-300 rounded-lg">
            <div class="flex items-start">
                <svg class="h-6 w-6 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-red-800">Akun Belum Terverifikasi</h3>
                    <p class="mt-1 text-sm text-red-700">
                        Anda harus menyelesaikan verifikasi akun sebelum dapat melakukan checkout. 
                        <a href="{{ route('customer.profile') }}" class="font-semibold underline">Lengkapi verifikasi sekarang →</a>
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($cartItems->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($cartItems as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="h-16 w-16 bg-gray-200 rounded flex items-center justify-center mr-4">
                                                @if($item->productUnit->product->image)
                                                    <img src="{{ Storage::url($item->productUnit->product->image) }}" alt="" class="h-full w-full object-cover rounded">
                                                @else
                                                    <span class="text-2xl">📷</span>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-semibold">{{ $item->productUnit->product->name }}</p>
                                                <p class="text-sm text-gray-500">{{ $item->productUnit->serial_number }}</p>
                                                <p class="text-sm text-primary-600">Rp {{ number_format($item->daily_rate, 0, ',', '.') }}/day</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm">{{ $item->start_date->format('d M Y H:i') }}</p>
                                        <p class="text-sm text-gray-500">to</p>
                                        <p class="text-sm">{{ $item->end_date->format('d M Y H:i') }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-semibold">{{ $item->days }}</span> days
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <form action="{{ route('cart.remove', $item) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-between">
                    <a href="{{ route('catalog.index') }}" class="text-primary-600 hover:underline">← Continue Shopping</a>
                    <form action="{{ route('cart.clear') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Clear Cart</button>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span>Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Deposit (30%)</span>
                            <span>Rp {{ number_format($total * 0.3, 0, ',', '.') }}</span>
                        </div>
                        <hr>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span class="text-primary-600">Rp {{ number_format($total, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($canCheckout)
                        <a href="{{ route('checkout.index') }}" class="w-full block text-center bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 transition">
                            Proceed to Checkout
                        </a>
                    @else
                        <button disabled class="w-full bg-gray-400 text-white py-3 rounded-lg font-semibold cursor-not-allowed">
                            Verifikasi Diperlukan
                        </button>
                        <p class="text-xs text-center text-gray-500 mt-2">
                            <a href="{{ route('customer.profile') }}" class="text-primary-600 hover:underline">Lengkapi verifikasi</a> untuk checkout
                        </p>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-16">
            <div class="text-6xl mb-4">🛒</div>
            <h2 class="text-xl font-semibold mb-2">Your cart is empty</h2>
            <p class="text-gray-600 mb-6">Looks like you haven't added any items yet.</p>
            <a href="{{ route('catalog.index') }}" class="bg-primary-600 text-white px-6 py-3 rounded-lg inline-block hover:bg-primary-700">
                Browse Catalog
            </a>
        </div>
    @endif
</div>
@endsection