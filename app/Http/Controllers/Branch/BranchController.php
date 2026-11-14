<?php

namespace App\Http\Controllers\Branch;

use App\Support\ScopeKey;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Traits\BranchValidation;
use App\Traits\InvalidatesCaches;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\BranchRequest;
use App\Repository\User\UserRepository;
use App\Repository\Lead\LeadsRepository;
use App\Services\Branches\BranchServices;
use App\Repository\Branches\BranchRepository;
use App\Repository\Sale\SaleRepository;
use App\Services\Authorization\ScopeResolverService;

class BranchController extends Controller
{
    use InvalidatesCaches, BranchValidation;
    protected $repository;
    protected $branchServices;

    public function __construct()
    {
        $this->repository = new BranchRepository();
        $this->branchServices = new BranchServices;
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', BranchModel::class);

        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only(array_keys($validated));

        // $filters = app(ScopeResolverService::class)->filters($filters);
        $branches = $this->repository->getCached(ScopeKey::GLOBAL, $filters, $filters['page'] ?? 1);
        return inertia('Branches/Index', [
            'branches' => $branches,
            'filters' => $filters
        ]);
    }
    public function store(BranchRequest $request)
    {
        $this->authorize('create', BranchModel::class, 'unauthorized_action');
        $validated = $request->validated();
        $user = auth()->user();
        $branch = $this->branchServices->store($validated, $user);
        $this->invalidateCaches([
            BranchRepository::class,
            UserRepository::class,
            LeadsRepository::class,
            SaleRepository::class
        ], [
            ScopeKey::user($user->id),
            ScopeKey::user($branch->created_by ?? $user->id),
            ScopeKey::branch($user->id_branch ?? $branch->id_branch),
            ScopeKey::branch($branch->id_branch ?? $user->id_branch),
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);
        return redirect()->back()->with('message', 'Cabang baru ' . $branch->name . ' berhasil ditambahkan!');
    }


    public function show(string $id)
    {
        $branch = BranchModel::with('branchPhone:id_branch,phone')
            ->findOrFail($id);
        $this->authorize('view', $branch);
        return response()->json($branch);
    }

    public function update(BranchRequest $request, string $id)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $branch = $request->resolveBranchModel();
        $this->authorize('update', $branch, 'unauthorized_action');
        $this->branchServices->update($branch, $validated, $user);
        $this->invalidateCaches([
            BranchRepository::class,
            UserRepository::class,
            LeadsRepository::class,
            SaleRepository::class
        ], [
            ScopeKey::user($user->id),
            ScopeKey::user($branch->created_by ?? $user->id),
            ScopeKey::user($branch->updated_by ?? $user->id),
            ScopeKey::branch($user->id_branch ?? $branch->id_branch),
            ScopeKey::branch($branch->id_branch ?? $user->id_branch),
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);
        return redirect()->back()->with('message', 'Perubahan data dari cabang ' . $branch->name . ' berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BranchRequest $request, string $id)
    {
        $user = auth()->user();

        $branch = $request->resolveBranchModel();

        $this->authorize('delete', $branch);

        DB::transaction(function () use ($branch) {
            $branch->branchPhone()->delete();
            $branch->delete();
        });
        $this->invalidateCaches([
            BranchRepository::class,
            UserRepository::class,
            LeadsRepository::class,
            SaleRepository::class
        ], [
            ScopeKey::user($user->id),
            ScopeKey::user($branch->created_by),
            ScopeKey::user($branch->updated_by),
            ScopeKey::branch($user->id_branch),
            ScopeKey::branch($branch->id_branch),
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ]);
        return redirect()->back()->with('message', 'Cabang ' . $branch->name . ' berhasil dihapus!');
    }
}
