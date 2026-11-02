<?php

namespace App\Services\User;

use App\Models\User;
use App\Support\ScopeKey;
use App\Models\ProfileModel;
use App\Traits\InvalidatesCaches;
use Illuminate\Support\Facades\DB;
use App\Repository\User\UserRepository;

class UserService
{
    use InvalidatesCaches;
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'id_branch' => $data['id_branch'],
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        ProfileModel::create([
            'id_user' => $user->id,
        ]);

        if (request()->filled('role')) {
            $user->assignRole($data['role']);
        }
        if (request()->has('permissions')) {
            $user->syncPermissions($data['permissions']);
        }

        return $user;
    }
    public function update(User $user, array $data, bool $canChangeBranch): User
    {
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);



        if ($canChangeBranch) {
            $user->id_branch = $data['id_branch'];
        }

        $user->save();

        ProfileModel::updateOrCreate([
            'id_user' => $user->id
        ], []);

        if (request()->filled('role')) {
            $user->syncRoles([
                $data['role']
            ]);
        }

        if (request()->has('permissions')) {
            $user->syncPermissions($data['permissions']);
        }

        return $user;
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->delete();
            $user->profile()->delete();
        });
    }
    public function toggleStatus(User $targetUser): User
    {
        return DB::transaction(function () use ($targetUser) {

            // Balikkan status boolean is_active
            $targetUser->is_active = !$targetUser->is_active;

            $targetUser->update();

            return $targetUser;
        });
    }
}
