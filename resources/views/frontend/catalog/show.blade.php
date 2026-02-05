@extends('layouts.frontend')

@section('title', $product->name)

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .flatpickr-day.flatpickr-disabled,
        .flatpickr-day.flatpickr-disabled:hover {
            color: #ffffff !important;
            background: #ef4444 !important;
            border-color: #ef4444 !important;
            text-decoration: none !important;
            opacity: 1 !important;
            cursor: not-allowed !important;
        }
    </style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="text-sm mb-6">
        <a href="{{ route('catalog.index') }}" class="text-gray-500 hover:text-primary-600">Catalog</a>
        <span class="mx-2 text-gray-400">/</span>
        <span class="text-gray-900">{{ $product->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Product Image -->
        <div>
            <div class="bg-gray-200 rounded-lg h-96 flex items-center justify-center">
                @if($product->image)
                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover rounded-lg">
                @else
                    <span class="text-9xl">📷</span>
                @endif
            </div>
        </div>

        <!-- Product Info -->
        <div>
            <span class="text-sm text-primary-600">{{ $product->category->name }}</span>
            <h1 class="text-3xl font-bold mt-2 mb-4">{{ $product->name }}</h1>
            <p class="text-gray-600 mb-6">{{ $product->description }}</p>

            <div class="text-3xl font-bold text-primary-600 mb-6">
                Rp {{ number_format($product->daily_rate, 0, ',', '.') }} <span class="text-base font-normal text-gray-500">/ day</span>
            </div>

            <!-- Availability -->
            <div class="mb-6">
                <p class="font-semibold mb-2">Available Units: {{ $availableUnits->count() }}</p>
            </div>

            @auth('customer')
                @php
                    $customer = auth('customer')->user();
                    $canRent = $customer->canRent();
                    $verificationStatus = $customer->getVerificationStatus();
                @endphp

                <!-- Verification Warning -->
                @if(!$canRent)
                    <div class="mb-6 p-4 rounded-lg border 
                        @if($verificationStatus === 'pending') bg-yellow-50 border-yellow-300 
                        @else bg-red-50 border-red-300 @endif">
                        <div class="flex items-start">
                            @if($verificationStatus === 'pending')
                                <svg class="h-5 w-5 text-yellow-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-yellow-800">Verifikasi Sedang Diproses</p>
                                    <p class="text-sm text-yellow-700">Anda dapat melakukan rental setelah verifikasi disetujui.</p>
                                </div>
                            @else
                                <svg class="h-5 w-5 text-red-400 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-red-800">Verifikasi Diperlukan</p>
                                    <p class="text-sm text-red-700">
                                        <a href="{{ route('customer.profile') }}" class="underline font-semibold">Lengkapi verifikasi</a> untuk dapat melakukan rental.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($availableUnits->count() > 0)
                    <!-- Booking Form -->
                    <form action="{{ route('cart.add') }}" method="POST" class="bg-gray-50 rounded-lg p-6">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Rentang Tanggal Sewa</label>
                            <div class="relative">
                                <input type="text" id="date_range" required placeholder="Pilih tanggal sewa..." 
                                    class="w-full border rounded-lg px-3 py-2 bg-white cursor-pointer" readonly>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Pickup</label>
                                <input type="time" name="pickup_time" id="pickup_time" required value="09:00"
                                    class="w-full border rounded-lg px-3 py-2 bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Jam Return</label>
                                <input type="time" name="return_time" id="return_time" required value="09:00"
                                    class="w-full border rounded-lg px-3 py-2 bg-white">
                            </div>
                        </div>

                        <input type="hidden" name="start_date" id="start_date">
                        <input type="hidden" name="end_date" id="end_date">

                @if($canRent)
                    @if($product->isFullyUnderMaintenance())
                        <button type="button" disabled class="w-full bg-red-500 text-white py-3 rounded-lg font-semibold cursor-not-allowed">
                            Under Maintenance
                        </button>
                    @else
                        <button type="submit" class="w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 transition">
                            Add to Cart
                        </button>
                    @endif
                @else
                    <button type="button" disabled class="w-full bg-gray-400 text-white py-3 rounded-lg font-semibold cursor-not-allowed">
                        Verifikasi Diperlukan
                    </button>
                @endif
                    </form>
                @else
                    <div class="bg-yellow-50 text-yellow-700 p-4 rounded-lg">
                        No units available at the moment.
                    </div>
                @endif
            @else
                <div class="bg-gray-50 rounded-lg p-6 text-center">
                    <p class="mb-4">Please login to book this equipment</p>
                    <a href="{{ route('customer.login') }}" class="bg-primary-600 text-white px-6 py-2 rounded-lg inline-block hover:bg-primary-700">Login to Book</a>
                </div>
            @endauth
        </div>
    </div>

    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
        <section class="mt-16">
            <h2 class="text-2xl font-bold mb-6">Related Products</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $related)
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                        <div class="h-40 bg-gray-200 flex items-center justify-center">
                            @if($related->image)
                                <img src="{{ Storage::url($related->image) }}" alt="{{ $related->name }}" class="h-full w-full object-cover">
                            @else
                                <span class="text-4xl">📷</span>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold mb-2">{{ $related->name }}</h3>
                            <p class="text-primary-600 font-bold">Rp {{ number_format($related->daily_rate, 0, ',', '.') }}/day</p>
                            <a href="{{ route('catalog.show', $related) }}" class="mt-2 block text-center text-primary-600 text-sm hover:underline">View Details</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookedDates = @json($bookedDates);
            const pickupTimeInput = document.getElementById('pickup_time');
            const returnTimeInput = document.getElementById('return_time');
            
            // URL Params
            const urlStartDate = "{{ request('start_date') }}";
            const urlEndDate = "{{ request('end_date') }}";
            const urlPickupTime = "{{ request('pickup_time') }}";
            const urlReturnTime = "{{ request('return_time') }}";

            // Load saved values
            const savedDates = localStorage.getItem('gearent_rental_dates');
            const savedPickup = localStorage.getItem('gearent_pickup_time');
            const savedReturn = localStorage.getItem('gearent_return_time');

            if (urlPickupTime) pickupTimeInput.value = urlPickupTime;
            else if (savedPickup) pickupTimeInput.value = savedPickup;

            if (urlReturnTime) returnTimeInput.value = urlReturnTime;
            else if (savedReturn) returnTimeInput.value = savedReturn;

            let selectedStart = null;
            let selectedEnd = null;

            const updateHiddenDates = () => {
                if (selectedStart && selectedEnd) {
                    const pickupTime = pickupTimeInput.value;
                    const returnTime = returnTimeInput.value;
                    
                    document.getElementById('start_date').value = `${selectedStart} ${pickupTime}:00`;
                    document.getElementById('end_date').value = `${selectedEnd} ${returnTime}:00`;

                    // Save times to localStorage
                    if (localStorage.getItem('gearent_pickup_time') !== pickupTime) {
                        localStorage.setItem('gearent_pickup_time', pickupTime);
                    }
                    if (localStorage.getItem('gearent_return_time') !== returnTime) {
                        localStorage.setItem('gearent_return_time', returnTime);
                    }
                }
            };

            let defaultDates = null;
            if (urlStartDate && urlEndDate) {
                defaultDates = [urlStartDate, urlEndDate];
                selectedStart = urlStartDate;
                selectedEnd = urlEndDate;
                updateHiddenDates();
                
                // Update localStorage to match current selection
                const dateStr = `${urlStartDate} to ${urlEndDate}`;
                if (localStorage.getItem('gearent_rental_dates') !== dateStr) {
                    localStorage.setItem('gearent_rental_dates', dateStr);
                }
            } else if (savedDates) {
                defaultDates = savedDates.split(' to ');
            }

            const fp = flatpickr("#date_range", {
                mode: "range",
                minDate: "today",
                dateFormat: "Y-m-d",
                disable: bookedDates,
                defaultDate: defaultDates,
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        selectedStart = instance.formatDate(selectedDates[0], "Y-m-d");
                        selectedEnd = instance.formatDate(selectedDates[1], "Y-m-d");
                        
                        // Save to localStorage if changed
                        if (localStorage.getItem('gearent_rental_dates') !== dateStr) {
                            localStorage.setItem('gearent_rental_dates', dateStr);
                        }
                        
                        updateHiddenDates();
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        selectedStart = instance.formatDate(selectedDates[0], "Y-m-d");
                        selectedEnd = instance.formatDate(selectedDates[1], "Y-m-d");
                        updateHiddenDates();
                    }
                }
            });

            pickupTimeInput.addEventListener('change', updateHiddenDates);
            returnTimeInput.addEventListener('change', updateHiddenDates);

            // Listen for changes from other tabs/windows
            window.addEventListener('storage', function(e) {
                if (e.key === 'gearent_rental_dates' && e.newValue) {
                    if (fp.input.value !== e.newValue) {
                        fp.setDate(e.newValue.split(' to '), true);
                    }
                }
                if (e.key === 'gearent_pickup_time' && e.newValue) {
                    if (pickupTimeInput.value !== e.newValue) {
                        pickupTimeInput.value = e.newValue;
                        updateHiddenDates();
                    }
                }
                if (e.key === 'gearent_return_time' && e.newValue) {
                    if (returnTimeInput.value !== e.newValue) {
                        returnTimeInput.value = e.newValue;
                        updateHiddenDates();
                    }
                }
            });
        });
    </script>
@endpush