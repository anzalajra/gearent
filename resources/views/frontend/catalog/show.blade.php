@extends('layouts.frontend')

@section('title', $product->name)

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
                @if($availableUnits->count() > 0)
                    <!-- Booking Form -->
                    <form action="{{ route('cart.add') }}" method="POST" class="bg-gray-50 rounded-lg p-6">
                        @csrf
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                <input type="datetime-local" name="start_date" required min="{{ now()->format('Y-m-d\TH:i') }}" class="w-full border rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                <input type="datetime-local" name="end_date" required class="w-full border rounded-lg px-3 py-2">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Unit</label>
                            <select name="product_unit_id" required class="w-full border rounded-lg px-3 py-2">
                                @foreach($availableUnits as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->serial_number }} - {{ ucfirst($unit->condition) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full bg-primary-600 text-white py-3 rounded-lg font-semibold hover:bg-primary-700 transition">
                            Add to Cart
                        </button>
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
                            <span class="text-4xl">📷</span>
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