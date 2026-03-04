<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Seeder untuk Central Database.
 * Membuat role dan user superadmin untuk Central Panel.
 * 
 * Jalankan: docker compose exec app php artisan db:seed --class=CentralSeeder
 */
class CentralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create central_admin role
        $centralAdminRole = Role::firstOrCreate(
            ['name' => 'central_admin', 'guard_name' => 'web']
        );
        
        // Create super_admin role (for backward compatibility)
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );
        
        // Create admin and staff roles (needed for User model events)
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        
        // Create basic permissions for central panel
        $centralPermissions = [
            'view_tenant',
            'create_tenant',
            'update_tenant',
            'delete_tenant',
            'view_subscription_plan',
            'create_subscription_plan',
            'update_subscription_plan',
            'delete_subscription_plan',
            'view_domain',
            'create_domain',
            'update_domain',
            'delete_domain',
        ];
        
        foreach ($centralPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
        
        // Assign all permissions to central_admin
        $centralAdminRole->syncPermissions($centralPermissions);
        $superAdminRole->syncPermissions($centralPermissions);
        
        // Create default superadmin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@zewalo.com'],
            [
                'name' => 'Zewalo Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Assign super_admin role
        if (!$admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }
        
        $this->command->info('✅ Central admin user created: admin@zewalo.com');
        $this->command->info('   Password: password');
        $this->command->info('   Please change the password after first login!');
    }
}
