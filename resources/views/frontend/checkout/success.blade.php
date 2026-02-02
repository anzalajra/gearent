@extends('layouts.frontend')

@section('title', 'Booking Confirmed')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-8">
        <div class="text-6xl mb-4">✅</div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Booking Submitted!</h1>
        <p class="text-gray-600">Thank you for your booking. We will contact you shortly to confirm.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Booking Details</h2>
        
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm text-gray-500">Booking Code</label>
                <p class="font-semibold text-lg">{{ $rental->rental_code }}</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500">Status</label>
                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm font-medium">Pending</span>
            </div>
            <div>
                <label class="block text-sm text-gray-500">Start Date</label>
                <p class="font-medium">{{ $rental->start_date->format('d M Y H:i') }}</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500">End Date</label>
                <p class="font-medium">{{ $rental->end_date->format('d M Y H:i') }}</p>
            </div>
        </div>

        <hr class="my-4">

        <h3 class="font-semibold mb-3">Items</h3>
        <div class="space-y-3">
            @foreach($rental->items as $item)
                <div class="flex justify-between">
                    <div>
                        <p class="font-medium">{{ $item->productUnit->product->name }}</p>
                        <p class="text-sm text-gray-500">{{ $item->days }} days × Rp {{ number_format($item->daily_rate, 0, ',', '.') }}</p>
                    </div>
                    <p class="font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                </div>
            @endforeach
        </div>

        <hr class="my-4">

        <div class="flex justify-between font-bold text-lg">
            <span>Total</span>
            <span class="text-primary-600">Rp {{ number_format($rental->total, 0, ',', '.') }}</span>
        </div>
        <div class="flex justify-between text-sm text-gray-600 mt-2">
            <span>Deposit Required</span>
            <span>Rp {{ number_format($rental->deposit, 0, ',', '.') }}</span>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="font-semibold text-blue-800 mb-2">What's Next?</h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• We will review your booking and contact you via phone/WhatsApp</li>
            <li>• Please prepare the deposit payment (30% of total)</li>
            <li>• Bring valid ID (KTP/SIM) when picking up equipment</li>
        </ul>
    </div>

    <div class="flex justify-center space-x-4">
        <a href="{{ route('customer.rentals') }}" class="bg-primary-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-700">
            View My Rentals
        </a>
        <a href="{{ route('catalog.index') }}" class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-300">
            Continue Browsing
        </a>
    </div>
</div>
@endsection