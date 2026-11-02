<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Traits\UserScope;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Traits\UserValidation;
use App\Services\User\UserService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Repository\User\UserRepository;
use App\Services\User\UserBusinessRuleService;
use Illuminate\Validation\ValidationException;
use App\Services\User\UserAuthorizationService;
use App\Services\Authorization\ScopeResolverService;
use App\Traits\InvalidatesCaches;
use Spatie\Permission\Models\Permission;
use App\Support\ScopeKey;

class UserController extends Controller
{
    use InvalidatesCaches, UserValidation, UserScope;
    protected $repository;
    protected $authorization;
    protected $service;
    protected $bussinessRule;
    protected $scopeResolverService = ScopeResolverService::class;

    public function __construct(UserRepository $employeeRepository)
    {
        $this->repository = $employeeRepository;
        $this->authorization = new UserAuthorizationService();
        $this->service = new UserService();
        $this->bussinessRule = new UserBusinessRuleService();
        $this->middleware('role:developer|super_admin');
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'sort_order' => 'nullable|string|in:oldest,latest',
            'status' => 'nullable|string|in:all,active,inactive',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only(array_keys($validated));

        $filters = app(ScopeResolverService::class)->filters($filters);

        $employee = $this->repository->getCached($filters['scope'], $filters, $filters['page'] ?? 1);

        $branches = BranchModel::query()
            ->select('id_branch', 'name')
            ->get();

        $permission = Permission::select('id', 'name')->get();
        return inertia('Employee/Index', [
            'branches' => $branches,
            'employee' => $employee,
            'permissions' => $permission,
            'filters'  => $filters
        ]);
    }

    public function show(string $id)
    {
        $user = User::with('branch', 'roles:id,name', 'permissions:id,name')
            ->findOrFail($id);
        return response()->json($user);
    }


    public function store(Request $request)
    {
        $user = auth()->user();

        $response = Gate::inspect('create', $user);
        if ($response->denied()) {
            throw ValidationException::withMessages([
                'unauthorized_action' => $response->message() // Mengambil pesan dari Policy
            ]);
        }

        $allowedRoles = $this->allowedRoles($user);
        $this->validationText($request->all(), $allowedRoles, $user, null, 'create');

        $data = $request->all();
        $branchId = $user->hasRole('developer') ? $data['id_branch'] : $user->id_branch;

        $this->bussinessRule->ensureUniqueRolePerBranch(new User(), $data['role'], $branchId, $data['is_active']);
        $newUser = $this->service->create($data);

        $this->invalidateCaches(UserRepository::class, ['global', 'global_super_admin', 'branch_' . $newUser->id_branch, 'user_' . $newUser->id]);
        return back()->with(
            'message',
            "Akun {$newUser->name} berhasil didaftarkan sebagai " . ucwords($data['role'] . " di cabang " . ucwords($newUser->branch->name))
        );
    }
    public function update(Request $request, string $id)
    {
        $currentUser = auth()->user();

        $targetUser = User::findOrFail($id);
        $oldBranchId = $targetUser->id_branch;

        if (!$targetUser) {
            throw ValidationException::withMessages([
                'unauthorized_action' => 'Data pegawai tidak ditemukan.'
            ]);
        }

        // 1. Pengecekan Policy untuk user yang sedang login dan user target yang akan diubah
        $this->authorize('update', $targetUser, 'unauthorized_action');

        // 2. Ambil daftar role yang boleh dimanipulasi oleh user yang sedang login
        $allowedRoles = $this->allowedRoles($currentUser);

        $validated = $this->validationText($request->all(), $allowedRoles, $currentUser, $targetUser);

        // Aturan Bisnis Khusus Supervisor
        $this->bussinessRule->ensureUniqueRolePerBranch($targetUser, $validated['role'], $validated['id_branch']);

        // Eksekusi Perubahan di Database Berikan akses ganti cabang untuk Developer dan Super Admin
        $canChangeBranch = $currentUser->hasAnyRole(['developer', 'super_admin']);

        $this->service->update($targetUser, $validated, $canChangeBranch);

        $this->clearUserCaches($targetUser, $currentUser->id, $oldBranchId);

        return redirect()->back()->with('message', "Data {$targetUser->name} berhasil diperbarui!");
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if (!$user) {
            throw ValidationException::withMessages([
                'error' => 'Data pegawai tidak ditemukan atau sudah dihapus sebelumnya.'
            ]);
        }

        // 🛡️ 1. Pengecekan Policy
        $this->authorize('delete', $user, 'unauthorized_action');

        $this->service->delete($user);
        // cek apakah user sudah dihapus atau belum
        if (!$user->trashed()) {
            throw ValidationException::withMessages([
                'error' => "Sistem gagal menghapus data secara aman."
            ]);
        }
        $this->clearUserCaches($user, auth()->id(), $user->getOriginal('id_branch'));

        return redirect()->back()->with('message', "Pegawai atas nama {$user->name} telah dihapus dari sistem");
    }

    /**
     * Mengubah status aktif/nonaktif pegawai (Untuk Super Admin & Developer)
     */
    public function toggleStatus(string $id)
    {
        $currentUser = auth()->user();
        $targetUser = User::findOrFail($id);

        // Cek ke UserPolicy@toggleStatus
        $this->authorize('toggleStatus', $targetUser);

        $updatedUser = $this->service->toggleStatus($targetUser);

        $this->clearUserCaches($targetUser, $currentUser->id, $targetUser->getOriginal('id_branch'));

        $statusText = $updatedUser->is_active ? 'diaktifkan kembali' : 'dinonaktifkan';
        return redirect()->back()->with('message', "Akun {$updatedUser->name} berhasil {$statusText}.");
    }

    /**
     * Helper terpusat untuk invalidate cache User
     */
    protected function clearUserCaches(
        User $targetUser,
        ?string $actorUserId = null,
        ?string $oldBranchId = null
    ): void {
        $this->invalidateCaches(
            [UserRepository::class],
            [
                $actorUserId ? ScopeKey::user($actorUserId) : null,
                $oldBranchId ? ScopeKey::branch($oldBranchId) : null,
                ScopeKey::user($targetUser->id),
                ScopeKey::branch($targetUser->id_branch),
                ScopeKey::GLOBAL,
                ScopeKey::GLOBAL_SUPER_ADMIN,
            ]
        );
    }

    private function allowedRoles(User $user): array|null|string|bool
    {
        $allowedRoles = match (true) {
            $user->hasRole('developer')   => ['super_admin', 'supervisor', 'admin', 'sales', 'teknisi'],
            $user->hasRole('super_admin') => ['supervisor', 'admin', 'sales', 'teknisi'],
            // $user->hasRole('supervisor')  => ['admin', 'sales', 'teknisi'],
            default                                 => []
        };
        return $allowedRoles;
    }
}
