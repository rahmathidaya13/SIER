<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\BranchModel;

use App\Models\ProfileModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\ValidationException;

class RegisterService
{
    public function store($validated)
    {
        DB::beginTransaction();
        try {
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

            event(new Registered($user));
            DB::commit();
            return $user;
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal membuat user: " . $error);
            throw $error;
        }
    }
    public function uniqueRolePerBranch(User $user, string $role, ?string $branchId = null)
    {
        if ($role !== 'supervisor') {
            return;
        }

        $exists = User::query()
            ->whereKeyNot($user->id)
            ->where('id_branch', $branchId)
            ->where('is_active', 'active')
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

                'closing-view',
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

                'closing-view',
                'closing-remaining',
                'closing-payment_cancel',

                'data-export'
            ],
            'sales' => [
                'product-view',

                'lead-view',
                'lead-create',
                'lead-edit',
                'lead-delete',
                'lead-convert',

                'closing-view',
                'closing-remaining',
                'closing-payment_cancel',

                'data-export'
            ],
            'teknisi' => [
                'product-view',

                'lead-view',
                'lead-create',
                'lead-edit',
                'lead-delete',
                'lead-convert',

                'closing-view',
                'closing-remaining',
                'closing-payment_cancel',

                'data-export'
            ],
            default => []
        };
        if (!empty($permissions)) {
            $user->syncPermissions($permissions);
        }
    }
}
