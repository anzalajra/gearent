@extends('layouts.frontend')

@section('title', 'Checkout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-8">Checkout</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Checkout Form -->
        <div class="lg:col-span-2">
            <form action="{{ route('checkout.process') }}" method="POST">
                @csrf

                <!-- Customer Info -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                            <p class="text-gray-900">{{ $customer->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <p class="text-gray-900">{{ $customer->email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <p class="text-gray-900">{{ $customer->phone ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <p class="text-gray-900">{{ $customer->address ?? '-' }}</p>
                        </div>
                    </div>
                    <a href="{{ route('customer.profile') }}" class="text-primary-600 text-sm hover:underline mt-2 inline-block">Update Profile</a>
                </div>

                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Order Items</h2>
                    <div class="space-y-4">
                        @foreach($cartItems as $item)
                            <div class="flex items-center justify-between py-3 border-b">
                                <div class="flex items-center">
                                    <div class="h-12 w-12 bg-gray-200 rounded flex items-center justify-center mr-4">
                                        <span class="text-xl">📷</span>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ $item->productUnit->product->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $item->start_date->format('d M Y') }} - {{ $item->end_date->format('d M Y') }} ({{ $item->days }} days)</p>
                                    </div>
                                </div>
                                <p class="font-semibold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Notes -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Additional Notes</h2>
                    <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2" placeholder="Any special requests or notes..."></textarea>
                </div>

                <!-- Terms -->
                <div class="bg-white rounded-lg shadow p-6">
                    <label class="flex items-start">
                        <input type="checkbox" name="agree_terms" required class="mt-1 mr-3">
                        <span class="text-sm text-gray-600">
                            I agree to the <a href="#" class="text-primary-600 hover:underline">Terms and Conditions</a> and understand that a deposit of 30% is required to confirm my booking.
                        </span>
                    </label>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-24">
                    <h2 class="text-lg font-semibold mb-4">Order Summary</h2>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($deposit > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Deposit</span>
                            <span>Rp {{ number_format($deposit, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <hr>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span class="text-primary-600">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 transition">
                        Confirm Booking
                    </button>

                    <p class="text-xs text-gray-500 mt-4 text-center">
                        By confirming, your booking will be submitted for review. We will contact you for payment confirmation.
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection