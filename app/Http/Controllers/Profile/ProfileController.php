<?php

namespace App\Http\Controllers\Profile;

use App\Models\User;
use App\Services\Profile\ProfileService;
use App\Traits\ProfileValidation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\User\UserRepository;
use App\Traits\BranchMutationHelper;

class ProfileController extends Controller
{
    use ProfileValidation, BranchMutationHelper;

    protected $service;
    public function __construct()
    {
        $this->service = new ProfileService();
    }
    public function show(string $id)
    {
        $query = User::with(['profile', 'branch', 'roles'])
            ->find($id);

        return response()->json($query);
    }

    public function update(string $id)
    {
        $user = auth()->user();
        $validate = $this->validateProfile(request()->all());
        $profile = User::find($id);
        $this->service->updateProfile($profile, $validate);
        $this->invalidateCaches([UserRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('message', 'Perubahan profile berhasil diperbarui!');
    }
}
