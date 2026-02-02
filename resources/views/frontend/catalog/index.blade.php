@extends('layouts.frontend')

@section('title', 'Catalog')

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
                            <a href="{{ route('catalog.show', $product) }}" class="mt-3 block text-center bg-primary-600 text-white py-2 rounded hover:bg-primary-700 transition">
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