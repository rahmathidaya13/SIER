<?php

namespace App\Http\Controllers\User;

use App\Actions\User\CreateUserAction;
use App\Actions\User\UpdateUserAction;
use App\Models\User;
use App\Models\BranchModel;
use App\Models\ProfileModel;
use Illuminate\Http\Request;
use App\Traits\UserValidation;
use App\Http\Controllers\Controller;
use App\Services\UserAuthorizationService;
use App\Traits\BranchMutationHelper;
use App\Traits\UserScope;

class UserController extends Controller
{
    use BranchMutationHelper, UserValidation, UserScope;
    protected $employeeRepository;
    protected $authorization;
    protected $actionCreate;
    protected $actionUpdate;
    public function __construct(UserRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
        $this->authorization = app(UserAuthorizationService::class);
        $this->actionCreate = app(CreateUserAction::class);
        $this->actionUpdate = app(UpdateUserAction::class);
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
        $employee = $this->employeeRepository->getCached($this->scopeId($user), $filters);
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
        $newUser = $this->actionCreate->execute($data);
        $this->invalidateCaches(UserRepository::class, ['global', $newUser->id_branch]);
        return back()->with(
            'message',
            "Akun {$newUser->name} berhasil didaftarkan sebagai " . ucwords($data['role'])
        );
    }
    public function update(Request $request, string $id)
    {
        $currentUser = auth()->user();
        $superAdmin = $currentUser->hasRole('super_admin');
        $user = User::find($id);
        $oldBranchId = $user->id_branch;
        $this->authorization->ensureCanUpdate($currentUser, $user);
        // Aturan role yang boleh didaftarkan
        $allowedRoles = $this->authorization->allowedRoles($currentUser);

        $this->validationText($request->all(), $allowedRoles, $currentUser, $user, 'edit');
        $this->actionUpdate->execute($user, $request->all(), $superAdmin);
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

        return redirect()->back()->with('message', "Akses pegawai {$name} telah dinonaktifkan.");
    }
}
