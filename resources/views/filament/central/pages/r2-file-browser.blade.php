<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Tenant Storage Overview --}}
        <x-filament::section collapsible>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-pie class="w-5 h-5" />
                    Ringkasan Penyimpanan per Tenant
                </div>
            </x-slot>

            @if(count($tenantStats) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="text-left py-2 px-3">Tenant</th>
                            <th class="text-right py-2 px-3">Jumlah File</th>
                            <th class="text-right py-2 px-3">Ukuran</th>
                            <th class="text-center py-2 px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenantStats as $stat)
                        <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="py-2 px-3">
                                <span class="font-medium">{{ $stat['tenant_name'] }}</span>
                                @if($stat['tenant_id'] !== 'central')
                                <span class="text-xs text-gray-500 ml-1">({{ $stat['tenant_id'] }})</span>
                                @endif
                            </td>
                            <td class="text-right py-2 px-3">
                                {{ number_format($stat['files_count']) }}
                            </td>
                            <td class="text-right py-2 px-3">
                                {{ $stat['size_formatted'] }}
                            </td>
                            <td class="text-center py-2 px-3">
                                <x-filament::button 
                                    wire:click="jumpToTenant('{{ $stat['directory'] }}')"
                                    size="xs"
                                    color="gray"
                                >
                                    <x-heroicon-m-folder-open class="w-3 h-3" />
                                </x-filament::button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-gray-500 text-sm">Belum ada data penyimpanan.</p>
            @endif
        </x-filament::section>

        {{-- Navigation Bar --}}
        <div class="flex items-center justify-between gap-4 flex-wrap bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm">
            <div class="flex items-center gap-2 flex-wrap">
                <x-filament::button 
                    wire:click="goToRoot" 
                    size="sm" 
                    color="gray"
                    :disabled="empty($currentPath)"
                >
                    <x-heroicon-m-home class="w-4 h-4" />
                </x-filament::button>

                <x-filament::button 
                    wire:click="goBack" 
                    size="sm" 
                    color="gray"
                    :disabled="empty($currentPath)"
                >
                    <x-heroicon-m-arrow-left class="w-4 h-4" />
                </x-filament::button>

                <div class="flex items-center text-sm">
                    <span class="text-gray-500">/</span>
                    @forelse($breadcrumbs as $index => $breadcrumb)
                        @if($index > 0)
                            <span class="text-gray-500 mx-1">/</span>
                        @endif
                        <button 
                            wire:click="navigateTo('{{ $breadcrumb['path'] }}')"
                            class="text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-200 px-1"
                        >
                            {{ $breadcrumb['name'] }}
                        </button>
                    @empty
                        <span class="text-gray-400 px-1">root</span>
                    @endforelse
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:change="jumpToTenant($event.target.value)">
                        <option value="">-- Pilih Tenant --</option>
                        @foreach($this->getTenants() as $path => $name)
                            <option value="{{ $path }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <x-filament::button wire:click="refresh" size="sm" color="gray">
                    <x-heroicon-m-arrow-path class="w-4 h-4" />
                </x-filament::button>

                @if(count($selectedItems) > 0)
                <x-filament::button 
                    wire:click="deleteSelected" 
                    wire:confirm="Apakah Anda yakin ingin menghapus {{ count($selectedItems) }} item yang dipilih?"
                    size="sm" 
                    color="danger"
                >
                    <x-heroicon-m-trash class="w-4 h-4 mr-1" />
                    Hapus ({{ count($selectedItems) }})
                </x-filament::button>
                @endif
            </div>
        </div>

        {{-- File List --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            @if($items->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <span class="sr-only">Select</span>
                        </th>
                        <th class="w-10 px-2 py-3">
                            <span class="sr-only">Type</span>
                        </th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 dark:text-gray-300">Nama</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600 dark:text-gray-300 w-32">Ukuran</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600 dark:text-gray-300 w-44">Terakhir Diubah</th>
                        <th class="w-24 px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($items as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-4 py-2">
                            <input 
                                type="checkbox" 
                                wire:model.live="selectedItems" 
                                value="{{ $item['path'] }}"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            >
                        </td>
                        <td class="px-2 py-2">
                            @if($item['type'] === 'directory')
                                <x-heroicon-o-folder class="w-6 h-6 text-amber-500" />
                            @elseif($this->isImage($item['extension'] ?? ''))
                                <x-heroicon-o-photo class="w-6 h-6 text-purple-500" />
                            @else
                                <x-heroicon-o-document class="w-6 h-6 text-gray-400" />
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($item['type'] === 'directory')
                                <button 
                                    wire:click="navigateTo('{{ $item['path'] }}')"
                                    class="font-medium text-primary-600 hover:text-primary-800 dark:text-primary-400"
                                >
                                    {{ $item['name'] }}
                                </button>
                                <span class="text-xs text-gray-400 ml-2">Folder</span>
                            @else
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ $item['name'] }}</span>
                                <span class="text-xs text-gray-400 ml-2 uppercase">{{ $item['extension'] ?? 'file' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right text-gray-600 dark:text-gray-400">
                            {{ $item['size_formatted'] ?? '-' }}
                        </td>
                        <td class="px-4 py-2 text-gray-600 dark:text-gray-400">
                            {{ $item['last_modified'] ?? '-' }}
                        </td>
                        <td class="px-4 py-2">
                            <div class="flex items-center justify-end gap-1">
                                @if($item['type'] === 'directory')
                                    <x-filament::icon-button
                                        wire:click="navigateTo('{{ $item['path'] }}')"
                                        icon="heroicon-m-folder-open"
                                        size="sm"
                                        color="gray"
                                        tooltip="Buka Folder"
                                    />
                                @else
                                    <a 
                                        href="{{ $this->getDownloadUrl($item['path']) }}" 
                                        target="_blank"
                                        class="fi-icon-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-sm h-8 w-8 fi-color-custom text-custom-500 hover:text-custom-600 focus-visible:ring-custom-500/50 dark:text-custom-400 dark:hover:text-custom-300"
                                        style="--c-400:var(--gray-400);--c-500:var(--gray-500);--c-600:var(--gray-600);"
                                    >
                                        <x-heroicon-m-arrow-down-tray class="w-5 h-5" />
                                    </a>
                                @endif
                                
                                <x-filament::icon-button
                                    wire:click="deleteItem('{{ $item['path'] }}', '{{ $item['type'] }}')"
                                    wire:confirm="Apakah Anda yakin ingin menghapus {{ $item['type'] === 'directory' ? 'folder ini beserta seluruh isinya' : 'file ini' }}?"
                                    icon="heroicon-m-trash"
                                    size="sm"
                                    color="danger"
                                    tooltip="Hapus"
                                />
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="flex flex-col items-center justify-center py-12">
                <x-heroicon-o-folder class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Folder Kosong</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada file atau folder di lokasi ini.</p>
            </div>
            @endif
        </div>

        {{-- Current Path Info --}}
        <div class="text-xs text-gray-500 dark:text-gray-400">
            Path saat ini: <code class="font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $currentPath ?: '/' }}</code>
        </div>
    </div>
</x-filament-panels::page>
