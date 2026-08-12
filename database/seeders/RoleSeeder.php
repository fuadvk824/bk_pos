<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage dashboard',
            'view mobile',
            'manage mobile',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $user  = Role::firstOrCreate(['name' => 'user']);
        $trialUser  = Role::firstOrCreate(['name' => 'trial-user']);

        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions(['view dashboard', 'view mobile', 'manage mobile']);
        $user->syncPermissions(['view mobile', 'manage mobile']);
        $trialUser->syncPermissions(['view mobile']);
    }
}
