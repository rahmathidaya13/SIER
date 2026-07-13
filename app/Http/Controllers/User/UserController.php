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
use App\Repository\User\UserRepository;
use App\Services\User\UserBusinessRuleService;
use App\Services\User\UserAuthorizationService;

class UserController extends Controller
{
    use BranchMutationHelper, UserValidation, UserScope;
    protected $repository;
    protected $authorization;
    protected $action;
    protected $bussinessRule;
    public function __construct(UserRepository $employeeRepository)
    {
        $this->repository = $employeeRepository;
        $this->authorization = new UserAuthorizationService();
        $this->action = new UserService();
        $this->bussinessRule = new UserBusinessRuleService();
        $this->middleware('role:super_admin|supervisor');
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

        $branches = BranchModel::query()
            ->when(
                $this->scopeId($user) !== 'global',
                fn($query) => $query->where('id_branch', $user->id_branch)
            )
            ->select('id_branch', 'name')
            ->get();
        $employee = $this->repository->getCached($this->scopeId($user), $filters);
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

        $this->authorization->ensureCanCreate($user, ['super_admin', 'supervisor']);

        $this->validationText($request->all(), $allowedRoles, $user, null, 'create');

        $data = $request->all();

        if (!$user->hasRole('super_admin')) {
            $data['id_branch'] = $user->id_branch;
        }

        $this->bussinessRule->ensureUniqueRolePerBranch(new User(), $data['role'], $data['id_branch'], $data['is_active']);
        $newUser = $this->action->create($data);

        $this->invalidateCaches(UserRepository::class, ['global', $newUser->id_branch]);
        return back()->with(
            'message',
            "Akun {$newUser->name} berhasil didaftarkan sebagai " . ucwords($data['role'] . " di cabang " . ucwords($newUser->branch->name))
        );
    }
    public function update(Request $request, string $id)
    {
        $currentUser = auth()->user();
        $superAdmin = $currentUser->hasRole('super_admin');

        $user = User::find($id);
        $oldBranchId = $user->id_branch;

        $this->authorization->ensureCanUpdate($currentUser, $user);

        $allowedRoles = $this->authorization->allowedRoles($currentUser);

        $this->validationText($request->all(), $allowedRoles, $currentUser, $user, 'edit');

        $this->bussinessRule->ensureUniqueRolePerBranch($user, $request['role'], $request['id_branch'], $request['is_active']);

        $this->action->update($user, $request->all(), $superAdmin);
        $this->invalidateCaches(UserRepository::class, ['global', $oldBranchId, $request->id_branch]);

        return redirect()->back()->with('message', "Data {$user->name} berhasil diperbarui!");
    }

    public function destroy(string $id)
    {
        $currentUser = auth()->user();

        $user = User::find($id);
        $branchId = $user->id_branch;
        $name = $user->name;
        $this->authorization->ensureCanDelete($currentUser, $user);

        $user->delete();
        $this->invalidateCaches(UserRepository::class, ['global', $branchId]);

        return redirect()->back()->with('message', "pegawai atas nama {$name} telah dihapus dari sistem");
    }
}
