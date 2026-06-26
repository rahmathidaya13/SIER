<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::create([
            'name'      => 'rahmat',
            'email'     => 'bos@pusat.com',
            'password'  => bcrypt('@Password123'),
            'email_verified_at' => now(),
            'id_branch' => null // <-- KUNCI SAKTI: Null artinya dia menguasai semua cabang
        ]);
        $superadmin->assignRole('super_admin');
    }
}
