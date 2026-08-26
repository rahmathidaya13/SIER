<?php

namespace App\Http\Controllers\Profile;

use App\Models\User;
use App\Services\Profile\ProfileService;
use App\Traits\ProfileValidation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repository\Lead\LeadsRepository;
use App\Repository\Sale\SaleRepository;
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
        $query = User::query()
            ->with(['profile:id_user,gender,phone,address,city,province,photo', 'branch:id_branch,name', 'roles:id,name'])
            ->find($id);
        return response()->json($query);
    }

    public function update(string $id)
    {
        $user = auth()->user();
        $validate = $this->validateProfile(request()->all());
        $profile = User::find($id);
        $this->service->updateProfile($profile, $validate);
        $this->invalidateCaches([
            UserRepository::class,
            SaleRepository::class,
            LeadsRepository::class
        ], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('prf-message', 'Perubahan profile berhasil diperbarui!');
    }
}
