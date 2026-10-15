<?php

namespace App\Http\Controllers\Auth;

use Throwable;
use App\Models\User;
use App\Support\ScopeKey;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Services\Otp\OtpService;
use App\Traits\RegisterValidation;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use Illuminate\Support\Facades\Auth;
use App\Services\Auth\RegisterService;
use App\Repository\User\UserRepository;
use App\Repository\Branches\BranchRepository;
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

        // validasi kode cabang sebelum membuat akun
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


            $request->session()->put(
                'otp_user_id',
                $user->id
            );
            $request->session()->save();

            $otpService = new OtpService();
            $otpService->send($user);

            // bersihkan cache
            $this->invalidateCaches([
                UserRepository::class,
                BranchRepository::class
            ], [
                ScopeKey::GLOBAL,
                ScopeKey::GLOBAL_SUPER_ADMIN,
                ScopeKey::branch($validated['id_branch']),
                ScopeKey::user($user->id)
            ]);
            return redirect()->route('verification.otp');
        } catch (ValidationException $er) {
            throw $er;
        } catch (Throwable $error) {
            report($error);

            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'error' => config('app.debug')
                        ? 'Gagal membuat akun: ' . $error->getMessage()
                        : 'Gagal membuat akun. Silakan coba lagi.',
                ]);
        }
    }
}
