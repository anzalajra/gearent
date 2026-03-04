<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Central Database Info --}}
        <x-filament::section>
            <x-slot name="heading">Central Database</x-slot>
            <x-slot name="description">Connection information for the central/landlord database</x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($databaseInfo as $label => $value)
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $label) }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white font-mono">{{ $value }}</dd>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Tenant Databases --}}
        <x-filament::section>
            <x-slot name="heading">Tenant Databases</x-slot>
            <x-slot name="description">List of all tenant databases</x-slot>
            
            @if(count($tenantDatabases) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Tenant ID</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Name</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Database</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Domains</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-500 dark:text-gray-400">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tenantDatabases as $tenant)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-3 px-4 font-mono text-gray-900 dark:text-white">{{ $tenant['id'] }}</td>
                                    <td class="py-3 px-4 text-gray-900 dark:text-white">{{ $tenant['name'] ?? '-' }}</td>
                                    <td class="py-3 px-4 font-mono text-gray-600 dark:text-gray-400">{{ $tenant['database'] }}</td>
                                    <td class="py-3 px-4 text-gray-600 dark:text-gray-400">{{ $tenant['domains'] ?: '-' }}</td>
                                    <td class="py-3 px-4">
                                        <span @class([
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' => $tenant['status'] === 'active',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' => $tenant['status'] === 'trial',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $tenant['status'] === 'inactive',
                                            'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100' => $tenant['status'] === 'suspended',
                                        ])>
                                            {{ ucfirst($tenant['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-circle-stack class="mx-auto h-12 w-12 text-gray-400" />
                    <p class="mt-2">No tenant databases found.</p>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
