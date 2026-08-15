<?php

namespace Database\Seeders;

use App\Models\BranchModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $headOfficeId = BranchModel::where('status', 'official')->first();
        $developer = User::create([
            'name'      => 'iamdeveloper',
            'email'     => 'rahmadlawrent6@gmail.com',
            'password'  => bcrypt('@Rahmad13'),
            'email_verified_at' => now(),
            'id_branch' => null
        ]);
        $developer->profile()->createOrFirst([
            'id_user' => $developer->id,
        ]);
        // beri hak akses developer ke semua permission
        $developer->assignRole('developer');
        $developer->givePermissionTo(Permission::all());

        // ini aku sample
        $superadmin = User::create([
            'name'      => 'rahmat',
            'email'     => 'bos@pusat.com',
            'password'  => bcrypt('@Password123'),
            'email_verified_at' => now(),
            'id_branch' => $headOfficeId->id_branch
        ]);
        $superadmin->profile()->createOrFirst([
            'id_user' => $superadmin->id,
        ]);
        $superadmin->assignRole('super_admin');
        // beri hak akses ke semua permission
        $superadmin->givePermissionTo(Permission::all());




    }
}
