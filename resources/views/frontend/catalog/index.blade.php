@extends('layouts.frontend')

@section('title', 'Catalog')

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
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <aside class="lg:w-64 flex-shrink-0">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-semibold mb-4">Filters</h3>
                <form action="{{ route('catalog.index') }}" method="GET">
                    <!-- Search -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>

                    <!-- Date Range -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rental Dates</label>
                        <div class="relative">
                            <input type="text" id="date_range" placeholder="Select dates..." 
                                class="w-full border rounded-lg px-3 py-2 text-sm bg-white cursor-pointer" readonly>
                            <input type="hidden" name="start_date" id="start_date" value="{{ request('start_date') }}">
                            <input type="hidden" name="end_date" id="end_date" value="{{ request('end_date') }}">
                        </div>
                    </div>

                    <!-- Time -->
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pickup</label>
                            <input type="time" name="pickup_time" value="{{ request('pickup_time', '09:00') }}" 
                                class="w-full border rounded-lg px-2 py-2 text-sm bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Return</label>
                            <input type="time" name="return_time" value="{{ request('return_time', '09:00') }}" 
                                class="w-full border rounded-lg px-2 py-2 text-sm bg-white">
                        </div>
                    </div>

                    <!-- Category -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sort -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select name="sort" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-primary-600 text-white py-2 rounded-lg hover:bg-primary-700">
                        Apply Filters
                    </button>
                </form>
            </div>
        </aside>

        <!-- Products Grid -->
        <div class="flex-1">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Equipment Catalog</h1>
                <p class="text-gray-600">{{ $products->total() }} products found</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($products as $product)
                    <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                        <div class="h-48 bg-gray-200 flex items-center justify-center">
                            @if($product->image)
                                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                            @else
                                <span class="text-6xl">📷</span>
                            @endif
                        </div>
                        <div class="p-4">
                            <p class="text-xs text-primary-600 mb-1">{{ $product->category->name }}</p>
                            <h3 class="font-semibold mb-2">{{ $product->name }}</h3>
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $product->description }}</p>
                            <div class="flex justify-between items-center">
                                <p class="text-primary-600 font-bold">Rp {{ number_format($product->daily_rate, 0, ',', '.') }}/day</p>
                                <span class="text-xs text-gray-500">{{ $product->units->where('status', 'available')->count() }} available</span>
                            </div>
                            <a href="{{ route('catalog.show', array_merge(['product' => $product], request()->only(['start_date', 'end_date', 'pickup_time', 'return_time']))) }}" class="mt-3 block text-center bg-primary-600 text-white py-2 rounded hover:bg-primary-700 transition">
                                View Details
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-500">No products found.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            // Initialize Flatpickr
            flatpickr("#date_range", {
                mode: "range",
                dateFormat: "Y-m-d",
                minDate: "today",
                defaultDate: [startDateInput.value, endDateInput.value],
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        startDateInput.value = instance.formatDate(selectedDates[0], "Y-m-d");
                        endDateInput.value = instance.formatDate(selectedDates[1], "Y-m-d");
                    }
                }
            });
        });
    </script>
@endpush