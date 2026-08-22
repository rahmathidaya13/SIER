<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\ProfileModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\BranchMutationHelper;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    public function updateProfile(User $user, array $validated)
    {
        DB::beginTransaction();
        try {

            $imageProfile = null;
            if (request()->hasFile('photo')) {
                if ($user->profile->photo && Storage::disk('public')->exists($user->profile->photo)) {
                    Storage::disk('public')->delete($user->profile->photo);
                }
                $imageProfile = request()->file('photo')
                    ->store('profile', 'public');
            } else {
                $imageProfile = $user->profile->photo;
            }

            $user->profile()->update([
                'gender' => $validated['gender'],
                'phone' => $validated['numberPhone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'photo' => $imageProfile ?? $user->profile->photo,
            ]);

            $user->update([
                'name' => $validated['name'] ?? $user->name,
            ]);

            DB::commit();
            return $user;
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal memperbarui Lead: " . $error->getMessage());
            throw $error;
        }
    }
}
