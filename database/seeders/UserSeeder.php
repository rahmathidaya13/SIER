<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\BranchModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $headOfficeId = BranchModel::where('status', 'official')->first();
        $branchOffice = BranchModel::where('name', 'like', '%Pekanbaru%')->first();
        if (!$branchOffice) {
            $branchOffice = BranchModel::where('status', '!=', 'official')->first() ?? $headOfficeId;
        }
        $defaultPassword = Hash::make('@Password123');

        $users = [
            [
                'name'        => 'iamdeveloper',
                'email'       => 'rahmadlawrent6@gmail.com',
                'password'    => Hash::make('@Rahmad13'),
                'id_branch'   => null,
                'role'        => 'developer',
                'permissions' => Permission::all(),
            ],
            [
                'name'        => 'rahmat',
                'email'       => 'bos@pusat.com',
                'password'    => $defaultPassword,
                'id_branch'   => $headOfficeId?->id_branch, // Kantor Pusat
                'role'        => 'super_admin',
                'permissions' => Permission::whereNotIn('name', ['users-delete'])->get(),
            ],
            [
                'name'        => 'Budi Supervisor',
                'email'       => 'spv@cabang.com',
                'password'    => $defaultPassword,
                'id_branch'   => $branchOffice?->id_branch, // Cabang Kantor
                'role'        => 'supervisor',
                'permissions' => $this->assignUserPermission('supervisor'),
            ],
            [
                'name'        => 'Siti Admin',
                'email'       => 'admin@cabang.com',
                'password'    => $defaultPassword,
                'id_branch'   => $branchOffice?->id_branch, // Cabang Kantor
                'role'        => 'admin',
                'permissions' => $this->assignUserPermission('admin'),
            ],
            [
                'name'        => 'Andi Sales',
                'email'       => 'sales@cabang.com',
                'password'    => $defaultPassword,
                'id_branch'   => $branchOffice?->id_branch, // Cabang Kantor
                'role'        => 'sales',
                'permissions' => $this->assignUserPermission('sales')
            ],
            [
                'name'        => 'Rudi Teknisi',
                'email'       => 'teknisi@cabang.com',
                'password'    => $defaultPassword,
                'id_branch'   => $branchOffice?->id_branch, // Cabang Kantor
                'role'        => 'teknisi',
                'permissions' => $this->assignUserPermission('teknisi'),
            ],
        ];

        foreach ($users as $item) {
            $user = User::updateOrCreate(
                ['email' => $item['email']], // Cek berdasarkan email agar tidak duplikat
                [
                    'name'              => $item['name'],
                    'password'          => $item['password'],
                    'email_verified_at' => now(),
                    'id_branch'         => $item['id_branch'],
                    'is_active'         => true,
                ]
            );

            // Buat profil kosong untuk user
            $user->profile()->firstOrCreate([
                'id_user' => $user->id,
            ]);
            // Pasang Role (syncRoles lebih aman dari duplikasi dibanding assignRole saat re-seed)
            $user->syncRoles([$item['role']]);

            // Jika ada direct permission khusus yang ingin diberikan ke user
            if (!empty($item['permissions'])) {
                $user->syncPermissions($item['permissions']);
            }
        }
    }

    protected function assignUserPermission(string $role)
    {
        $permissions = match ($role) {
            'supervisor' => [
                'product-view',

                'lead-view',
                'lead-create',
                'lead-edit',
                'lead-delete',
                'lead-convert',

                'customer-view',
                'customer-create',
                'customer-edit',
                'customer-delete',

                'closing-view',
                'closing-remaining',
                'closing-payment_cancel',
                'closing-create',

                'data-export'
            ],
            'admin' => [
                'product-view',

                'lead-view',
                'lead-create',
                'lead-edit',
                'lead-delete',
                'lead-convert',

                'customer-view',
                'customer-create',
                'customer-edit',
                'customer-delete',

                'closing-view',
                'closing-remaining',
                'closing-payment_cancel',
                'closing-create',


                'data-export'
            ],
            'sales' => [
                'product-view',

                'lead-view',
                'lead-create',
                'lead-edit',
                'lead-delete',
                'lead-convert',

                'customer-view',
                'customer-create',
                'customer-edit',
                'customer-delete',

                'closing-view',
                'closing-remaining',
                'closing-payment_cancel',
                'closing-create',


                'data-export'
            ],
            'teknisi' => [
                'product-view',

                'lead-view',
                'lead-create',
                'lead-edit',
                'lead-delete',
                'lead-convert',

                'customer-view',
                'customer-create',
                'customer-edit',
                'customer-delete',

                'closing-view',
                'closing-remaining',
                'closing-payment_cancel',
                'closing-create',


                'data-export'
            ],
            default => []
        };

        return $permissions;
    }
}
