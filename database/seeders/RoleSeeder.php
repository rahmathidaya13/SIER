<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles
        $defaults = [
            ['name' => 'developer', 'guard_name' => 'web'],
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'supervisor', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'sales', 'guard_name' => 'web'],
            ['name' => 'teknisi', 'guard_name' => 'web'],
        ];

        $permissions = [
            // Modul Produk
            'product-view',
            'product-create',
            'product-edit',
            'product-delete',

            // Modul Leads
            'lead-view',
            'lead-create',
            'lead-edit',
            'lead-delete',
            'lead-convert',

            // Modul Closing/Terjual
            'closing-view',
            'closing-remaining',
            'closing-payment_cancel',

            // Modul Channel
            'channel-view',
            'channel-create',
            'channel-edit',
            'channel-delete',

            'branch-view',
            'branch-create',
            'branch-edit',
            'branch-delete',

            // Modul User
            'users-view',
            'users-create',
            'users-edit',
            'users-delete',

            // Aksi Khusus
            'data-export',
        ];

        foreach ($defaults as $data) {
            Role::firstOrCreate(['name' => $data['name']], $data);
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
