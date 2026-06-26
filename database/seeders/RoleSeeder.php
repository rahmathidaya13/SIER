<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $defaults = [
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'supervisor', 'guard_name' => 'web'],
            ['name' => 'admin', 'guard_name' => 'web'],
            ['name' => 'sales', 'guard_name' => 'web'],
            ['name' => 'teknisi', 'guard_name' => 'web'],
        ];
        foreach ($defaults as $data) {
            Role::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
