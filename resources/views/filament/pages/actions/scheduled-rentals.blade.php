<div class="p-4">
    <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5">
        <thead>
            <tr class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                <th class="px-4 py-2">Rental Code</th>
                <th class="px-4 py-2">Customer</th>
                <th class="px-4 py-2">Unit SN</th>
                <th class="px-4 py-2">Start Date</th>
                <th class="px-4 py-2">End Date</th>
                <th class="px-4 py-2 text-center">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
            @forelse($rentals as $item)
                @php
                    $status = $item->rental->getRealTimeStatus();
                    $badgeClass = match($status) {
                        'pending' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                        'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                        'completed' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                        'late_pickup', 'late_return' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
                    };
                @endphp
                <tr class="text-sm">
                    <td class="px-4 py-3 font-medium text-primary-600 dark:text-primary-400">
                        {{ $item->rental->rental_code }}
                    </td>
                    <td class="px-4 py-3">
                        {{ $item->rental->customer->name }}
                    </td>
                    <td class="px-4 py-3">
                        {{ $item->productUnit->serial_number }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        {{ $item->rental->start_date->format('d M Y H:i') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        {{ $item->rental->end_date->format('d M Y H:i') }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 italic">
                        No scheduled rentals found for this product.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
