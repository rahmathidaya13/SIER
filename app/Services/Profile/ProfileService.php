<?php

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProfileService
{
    public function updateProfile(User $user, array $validated)
    {
        DB::beginTransaction();
        try {

            $profile = $user->profile;

            $imageProfile = $profile->photo;

            if (request()->hasFile('photo')) {
                $file = request()->file('photo');
                $fileName = Str::ulid() . '.webp';
                $path = 'profile/' . $fileName;
                $image = Image::read($file)
                    ->coverDown(256, 256)
                    ->toWebp(75);

                Storage::disk('public')->put($path, $image);

                if ($profile->photo && Storage::disk('public')->exists($profile->photo)) {
                    Storage::disk('public')->delete($profile->photo);
                }
                $imageProfile = $path;
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
            return $user->fresh('profile');
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal memperbarui Lead: " . $error->getMessage());
            throw $error;
        }
    }
}
