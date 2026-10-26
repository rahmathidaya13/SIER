<?php

namespace App\Http\Controllers\Branch;

use App\Models\User;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Traits\BranchValidation;
use App\Http\Controllers\Controller;
use App\Services\Branches\BranchServices;
use App\Repository\Branches\BranchRepository;
use App\Repository\User\UserRepository;
use Illuminate\Validation\ValidationException;
use App\Services\Authorization\ScopeResolverService;
use App\Traits\InvalidatesCaches;

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
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only(array_keys($validated));

        $filters = app(ScopeResolverService::class)->filters($filters);
        $branches = $this->repository->getCached($filters['scope'], $filters, $filters['page'] ?? 1);
        return inertia('Branches/Index', [
            'branches' => $branches,
            'filters' => $filters
        ]);
    }
    public function store(Request $request)
    {
        $validated = $this->branchValidation($request->all());
        $user = auth()->user();
        try {
            $branch = $this->branchServices->store($validated, $user);
            $this->invalidateCaches([BranchRepository::class, UserRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
            return redirect()->back()->with('message', 'Cabang baru ' . $branch->name . ' berhasil ditambahkan!');
        } catch (\Exception $error) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menyimpan data: ' . $error->getMessage()]);
        }
    }


    public function show(string $id)
    {
        $branch = BranchModel::with('branchPhone:id_branch,phone')
            ->findOrFail($id);
        return response()->json($branch);
    }

    public function update(Request $request, string $id)
    {
        $validated = $this->branchValidation($request->all());
        $user = auth()->user();

        $branch = BranchModel::find($id);

        // periksa apakah ada perubahan status dari aktif ke non-aktif
        $isDeactivating = $branch->is_active !== $validated['isActive'];
        if ($isDeactivating) {
            $hasActiveUser = $branch->users()->where('is_active', 'active')->exists();
            if ($hasActiveUser) {
                throw ValidationException::withMessages([
                    'isActive' => 'Tidak dapat menonaktifkan cabang ' . ucwords($branch->name) . ' karena masih memiliki pengguna yang aktif.',
                ]);
            }
        }

        try {
            $this->branchServices->update($branch, $validated, $user);
            $this->invalidateCaches([BranchRepository::class, UserRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
            return redirect()->back()->with('message', 'Perubahan data dari cabang ' . $branch->name . ' berhasil diperbarui!');
        } catch (\Exception $error) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui data: ' . $error->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth()->user();
        $branch = BranchModel::find($id);

        $isDeactivating = $branch->users()->where('is_active',  'active')->exists();
        if ($isDeactivating) {
            throw ValidationException::withMessages([
                'error' => 'Tidak dapat menghapus cabang ' . ucwords($branch->name) . ' saat ini masih memiliki pengguna yang aktif.'
            ]);
        }

        if (!$user->hasRole('super_admin')) {
            throw ValidationException::withMessages([
                'error' => 'Anda tidak memiliki akses untuk menghapus cabang!'
            ]);
        }

        $branch->delete();
        $branch->branchPhone()->delete();
        $this->invalidateCaches([BranchRepository::class], ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]);
        return redirect()->back()->with('message', 'Cabang ' . $branch->name . ' berhasil dihapus!');
    }
}
