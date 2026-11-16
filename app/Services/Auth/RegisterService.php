<?php

namespace App\Services\Auth;

use App\Models\User;

use App\Models\ProfileModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterService
{
    public function store($validated)
    {
        return DB::transaction(function () use ($validated) {
            $user = User::create([
                'name'      => $validated['name'],
                'email'     => $validated['email'],
                'password'  => Hash::make($validated['password']),
                'id_branch' => $validated['id_branch'],
                'is_active' => true,
            ]);


            ProfileModel::create([
                'id_user' => $user->id,
            ]);

            $user->assignRole($validated['role']);
            $this->assignUserPermission($user, $validated['role']);
            return $user;
        });

    }
    public function uniqueRolePerBranch(User $user, string $role, ?string $branchId = null)
    {
        if ($role !== 'supervisor') {
            return;
        }

        $exists = User::query()
            ->whereKeyNot($user->id)
            ->where('id_branch', $branchId)
            ->where('is_active', true)
            ->role('supervisor')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'role' => 'Cabang ini sudah memiliki Supervisor aktif. Lapor ke Admin untuk Nonaktifkan Supervisor yang ada terlebih dahulu.',
            ]);
        }
    }
    private function assignUserPermission(User $user, string $role)
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
                // 'closing-create',
                'closing-remaining',
                'closing-payment_cancel',

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
                // 'closing-payment_cancel',
                // 'closing-create',


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
                // 'closing-payment_cancel',
                // 'closing-create',


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
                // 'closing-payment_cancel',
                // 'closing-create',


                'data-export'
            ],
            default => []
        };
        if (!empty($permissions)) {
            $user->syncPermissions($permissions);
        }
    }
}
