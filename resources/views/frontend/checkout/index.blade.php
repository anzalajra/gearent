@extends('layouts.frontend')

@section('title', 'Checkout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-8">Checkout</h1>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Checkout Form -->
        <form action="{{ route('checkout.process') }}" method="POST" class="contents">
            @csrf
            <div class="lg:col-span-2">
                <!-- Customer Info -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4">Customer Information</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                    
                    <div class="mb-6 border-b pb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Kode Diskon</label>
                        <div class="flex gap-2">
                            <input type="text" id="discount_code" value="{{ session('checkout_discount_code') }}" class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="Masukkan kode">
                            <button type="button" id="apply_discount" class="bg-gray-800 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition">Pakai</button>
                        </div>
                        <p id="discount_message" class="text-xs mt-2 {{ session('checkout_discount_amount') ? 'text-green-600' : 'hidden' }}">
                            {{ session('checkout_discount_amount') ? 'Diskon berhasil diterapkan!' : '' }}
                        </p>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
                        </div>
                        
                        <div id="discount_row" class="flex justify-between text-green-600 {{ isset($discountAmount) && $discountAmount > 0 ? '' : 'hidden' }}">
                            <span>Discount</span>
                            <span id="discount_amount">-Rp {{ number_format($discountAmount ?? 0, 0, ',', '.') }}</span>
                        </div>

                        @if($deposit > 0)
                        <div class="flex justify-between">
                            <span class="text-gray-600">Deposit</span>
                            <span id="deposit_amount">Rp {{ number_format($deposit, 0, ',', '.') }}</span>
                        </div>
                        @endif
                        <hr>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total</span>
                            <span class="text-primary-600" id="grand_total">Rp {{ number_format(($subtotal - ($discountAmount ?? 0)), 0, ',', '.') }}</span>
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

<script>
    document.getElementById('apply_discount').addEventListener('click', function() {
        const code = document.getElementById('discount_code').value;
        const btn = this;
        const msg = document.getElementById('discount_message');
        
        if (!code) return;

        btn.disabled = true;
        btn.innerHTML = '...';
        msg.classList.add('hidden');
        msg.className = 'text-xs mt-2 hidden';

        fetch('{{ route("checkout.validate-discount") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = 'Pakai';
            msg.classList.remove('hidden');
            msg.textContent = data.message;
            
            if (data.valid) {
                msg.classList.add('text-green-600');
                msg.classList.remove('text-red-600');
                
                // Update Summary
                document.getElementById('discount_row').classList.remove('hidden');
                document.getElementById('discount_amount').textContent = '-Rp ' + new Intl.NumberFormat('id-ID').format(data.discount_amount);
                
                // Update Total (Total = Subtotal - Discount)
                // Note: The controller returns new_total = Subtotal - Discount + Deposit? 
                // Let's stick to what we decided: Total in view is just Subtotal - Discount.
                // Or if we follow my controller logic: new_total was Subtotal - Discount. 
                // Wait, in controller: 'new_total' => $newTotal + $deposit
                // Let's recalculate in JS to be sure what we display matches the view logic.
                // View Logic: Total = Subtotal - Discount.
                
                const newTotal = data.new_subtotal - data.discount_amount;
                document.getElementById('grand_total').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(newTotal);
                
                // If deposit changes
                // document.getElementById('deposit_amount').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.new_deposit);
            } else {
                msg.classList.add('text-red-600');
                msg.classList.remove('text-green-600');
                
                // Hide discount row if invalid?
                // document.getElementById('discount_row').classList.add('hidden');
                // Or keep previous valid state? 
                // If invalid, maybe clear previous discount?
                // The controller doesn't clear session on failure.
                // Ideally if user types wrong code, we just show error, don't remove existing valid code.
            }
        })
        .catch(error => {
            console.error('Error:', error);
            btn.disabled = false;
            btn.innerHTML = 'Pakai';
            msg.classList.remove('hidden');
            msg.textContent = 'Terjadi kesalahan, silakan coba lagi.';
            msg.classList.add('text-red-600');
        });
    });
</script>
@endsection