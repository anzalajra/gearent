<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Super Admin (Full Access)
        // Handled by Shield's config (super_admin user checks)
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        
        // 2. Admin (Manage rentals, customers)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        
        // Match permissions ending with :Rental, :Customer, etc.
        // Format is {Permission}:{Resource} e.g. ViewAny:Rental
        $adminResources = [
            'Rental',
            'Customer',
            'Category', // CustomerCategory? Resource name might be CustomerCategory
            'CustomerCategory',
            'Brand',
            'Product',
            'ProductUnit',
            'Discount', // Likely needed
            'Delivery', // Likely needed for rentals
        ];

        $adminPermissions = Permission::query()
            ->where(function ($query) use ($adminResources) {
                foreach ($adminResources as $resource) {
                    $query->orWhere('name', 'like', "%:$resource");
                }
            })
            ->get();

        $adminRole->syncPermissions($adminPermissions);

        // 3. Staff (Process pickup/return only)
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $staffPermissions = Permission::query()
            ->whereIn('name', [
                'ViewAny:Rental',
                'View:Rental',
                'Update:Rental',
                'ViewAny:Customer',
                'View:Customer',
                'ViewAny:Delivery', // Likely needed for pickup/return
                'View:Delivery',
                'Update:Delivery',
                'ViewAny:ProductUnit', // Needed to see items
                'View:ProductUnit',
            ])
            ->get();

        $staffRole->syncPermissions($staffPermissions);
        
        $this->command->info('Roles and Permissions seeded successfully with Shield naming convention!');
    }
}
