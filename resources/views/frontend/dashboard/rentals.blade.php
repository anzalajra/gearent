@extends('layouts.frontend')

@section('title', 'My Rentals')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-8">My Rentals</h1>

    @if($rentals->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rental Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($rentals as $rental)
                        <tr>
                            <td class="px-6 py-4 font-semibold">{{ $rental->rental_code }}</td>
                            <td class="px-6 py-4">
                                <p class="text-sm">{{ $rental->start_date->format('d M Y') }}</p>
                                <p class="text-sm text-gray-500">to {{ $rental->end_date->format('d M Y') }}</p>
                            </td>
                            <td class="px-6 py-4">{{ $rental->items->count() }} item(s)</td>
                            <td class="px-6 py-4 font-semibold">Rp {{ number_format($rental->total, 0, ',', '.') }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-medium
                                    @if($rental->status == 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($rental->status == 'active') bg-green-100 text-green-800
                                    @elseif($rental->status == 'completed') bg-blue-100 text-blue-800
                                    @elseif($rental->status == 'cancelled') bg-gray-100 text-gray-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $rental->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('customer.rental.detail', $rental->id) }}" class="text-primary-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $rentals->links() }}
        </div>
    @else
        <div class="text-center py-16 bg-white rounded-lg shadow">
            <div class="text-6xl mb-4">📋</div>
            <h2 class="text-xl font-semibold mb-2">No rentals yet</h2>
            <p class="text-gray-600 mb-6">Start browsing our catalog to rent equipment.</p>
            <a href="{{ route('catalog.index') }}" class="bg-primary-600 text-white px-6 py-3 rounded-lg inline-block hover:bg-primary-700">
                Browse Catalog
            </a>
        </div>
    @endif
</div>
@endsection