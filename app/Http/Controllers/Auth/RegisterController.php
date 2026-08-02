<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Traits\RegisterValidation;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Repository\Branches\BranchRepository;
use App\Traits\BranchMutationHelper;
use App\Services\Auth\RegisterService;
use App\Repository\User\UserRepository;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    use RegisterValidation, BranchMutationHelper;
    protected $services;
    public function __construct()
    {
        $this->services = new RegisterService();
    }
    public function create()
    {
        $roles = Role::select('id', 'name')
            ->whereNotIn('name', ['super_admin', 'developer'])->get();
        $branches = BranchModel::query()
            ->where('is_active', 'active')
            ->select('id_branch', 'name')
            ->get();
        return inertia('Auth/Register', [
            'roles' => $roles,
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRegister($request->all());

        // validasi kode cabang
        $branch = BranchModel::where('id_branch', $validated['id_branch'])->first();
        if (!$branch || $branch->branch_code !== $validated['branch_code']) {
            throw ValidationException::withMessages([
                'branch_code' => 'Kode Cabang tidak sesuai'
            ]);
        }

        try {
            // validasi akun supervisor per cabang agar tidak duplikat
            $this->services->uniqueRolePerBranch(new User(), $validated['role'], $validated['id_branch']);
            $user = $this->services->store($validated);

            // bersihkan cache
            $this->invalidateCaches([UserRepository::class, BranchRepository::class], ['global', 'global_super_admin', 'branch_' . $validated['id_branch'], 'user_' . $user->id]);
            return redirect()->route('login')->with('message', 'Pendaftaran Akun baru berhasil. Silahkan login.');
        } catch (\Exception $error) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal membuat akun: ' . $error->getMessage()]);
        }
    }
}
