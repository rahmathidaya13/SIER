<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Traits\UserScope;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Traits\UserValidation;
use App\Services\User\UserService;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use Illuminate\Support\Facades\Gate;
use App\Repository\User\UserRepository;
use App\Services\User\UserBusinessRuleService;
use Illuminate\Validation\ValidationException;
use App\Services\User\UserAuthorizationService;
use App\Services\Authorization\ScopeResolverService;

class UserController extends Controller
{
    use BranchMutationHelper, UserValidation, UserScope;
    protected $repository;
    protected $authorization;
    protected $action;
    protected $bussinessRule;
    protected $scopeResolverService = ScopeResolverService::class;
    public function __construct(UserRepository $employeeRepository)
    {
        $this->repository = $employeeRepository;
        $this->authorization = new UserAuthorizationService();
        $this->action = new UserService();
        $this->bussinessRule = new UserBusinessRuleService();
        $this->middleware('role:developer|super_admin|supervisor');
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        $filters = $request->only([
            'keyword',
            'limit',
            'status',
            'sort_order',
            'page',
        ]);
        $filters = app(ScopeResolverService::class)->filters($filters);
        $employee = $this->repository->getCached($filters['scope'], $filters);
        $branches = BranchModel::query()
            ->select('id_branch', 'name')
            ->get();
        return inertia('Employee/Index', [
            'branches' => $branches,
            'employee' => $employee,
            'filters'  => $filters
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $allowedRoles = $this->authorization->allowedRoles($user);
        $this->validationText($request->all(), $allowedRoles, $user, null, 'create');

        $data = $request->all();
        $branchId = $user->hasRole('developer') ? $data['id_branch'] : $user->id_branch;

        $this->bussinessRule->ensureUniqueRolePerBranch(new User(), $data['role'], $branchId, $data['is_active']);
        $newUser = $this->action->create($data);

        $this->invalidateCaches(UserRepository::class, ['global', 'global_super_admin', 'branch_' . $newUser->id_branch]);
        return back()->with(
            'message',
            "Akun {$newUser->name} berhasil didaftarkan sebagai " . ucwords($data['role'] . " di cabang " . ucwords($newUser->branch->name))
        );
    }
    public function update(Request $request, string $id)
    {
        $currentUser = auth()->user();

        $user = User::find($id);
        $oldBranchId = $user->id_branch;

        // 1. Cek Hierarki (Apakah boleh mengedit?)
        $this->authorization->ensureCanUpdate($currentUser, $user);

        // 2. Ambil daftar role yang boleh dimanipulasi oleh user yang sedang login
        $allowedRoles = $this->authorization->allowedRoles($currentUser);

        $this->validationText($request->all(), $allowedRoles, $currentUser, $user, 'edit');

        // Aturan Bisnis Khusus Supervisor
        $this->bussinessRule->ensureUniqueRolePerBranch($user, $request['role'], $request['id_branch'], $request['is_active']);

        // Eksekusi Perubahan di Database Berikan akses ganti cabang untuk Developer dan Super Admin
        $canChangeBranch = $currentUser->hasAnyRole(['developer', 'super_admin']);
        $this->action->update($user, $request->all(), $canChangeBranch);

        $this->invalidateCaches(UserRepository::class, ['global', 'global_super_admin',  'branch_' . $oldBranchId, 'branch_' . $request->id_branch]);

        return redirect()->back()->with('message', "Data {$user->name} berhasil diperbarui!");
    }

    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            throw ValidationException::withMessages([
                'error' => 'Data pegawai tidak ditemukan atau sudah dihapus sebelumnya.'
            ]);
        }

        // 🛡️ 1. Pengecekan Policy
        $response = Gate::inspect('delete', $user);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message() // Mengambil pesan dari Policy
            ]);
        }

        $branchId = $user->id_branch;
        $name = $user->name;
        $user->delete();

        if (!$user->trashed()) {
            throw ValidationException::withMessages([
                'error' => "Sistem gagal menghapus data {$name} secara aman."
            ]);
        }

        $this->invalidateCaches(UserRepository::class, ['global', 'global_super_admin', 'branch_' . $branchId]);

        return redirect()->back()->with('message', "Pegawai atas nama {$name} telah dihapus dari sistem");
    }
}
