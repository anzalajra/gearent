<x-filament-panels::page>
    <div
        x-data="{
            activeTab: 'users',
            tabs: [
                { id: 'users', label: 'Users' },
                { id: 'roles', label: 'Roles' },
            ]
        }"
    >
        <div class="mb-6">
            <div class="border-b border-gray-200 dark:border-white/10">
                <nav class="-mb-px flex gap-6" aria-label="Tabs">
                    <template x-for="tab in tabs" :key="tab.id">
                        <button
                            @click="activeTab = tab.id"
                            :class="activeTab === tab.id ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:text-gray-300'"
                            class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium"
                            x-text="tab.label"
                        ></button>
                    </template>
                </nav>
            </div>
        </div>

        <div x-show="activeTab === 'users'">
            @livewire(\App\Livewire\UsersListWidget::class)
        </div>

        <div x-show="activeTab === 'roles'">
            @livewire(\App\Livewire\RolesListWidget::class)
        </div>
    </div>
</x-filament-panels::page>
