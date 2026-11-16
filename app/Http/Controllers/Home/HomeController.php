<?php

namespace App\Http\Controllers\Home;

use App\Models\BranchModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $services;
    public function __construct(protected DashboardService $dashboardService)
    {
        $this->services = $dashboardService;
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        $isGlobalAdmin = $user->hasAnyRole(['developer', 'super_admin']);

        // Ambil daftar cabang 1 kali saja untuk validasi sekaligus dropdown admin pusat
        $allBranches = BranchModel::select('id_branch', 'name', 'status')->get();

        $validated = $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:9999',
            'branch' => ['nullable', 'string', Rule::in($allBranches->pluck('name')->all())],
        ]);

        $month = $validated['month'] ?? now()->month;
        $year  = $validated['year'] ?? now()->year;


        if ($isGlobalAdmin) {
            // Prioritaskan pilihan dropdown; jika kosong, otomatis arahkan ke cabang berstatus 'official'
            $defaultBranch = $allBranches->firstWhere('status', 'official')
                ?? $allBranches->firstWhere('id_branch', $user->id_branch)
                ?? $allBranches->first();

            $selectedBranch = !empty($validated['branch'])
                ? $allBranches->firstWhere('name', $validated['branch'])
                : $defaultBranch;

            $branchId   = $selectedBranch?->id_branch;
            $branchName = $selectedBranch?->name ?? '';
        } else {
            // Supervisor dan role di bawahnya selalu terkunci ke cabangnya sendiri
            $branchId   = $user->id_branch;
            $branchName = $allBranches->firstWhere('id_branch', $user->id_branch)?->name ?? '';
        }

        // $branchId = null;
        // if ($isGlobalAdmin && $branchName !== 'all') {
        //     $branchId = $allBranches->where('name', $branchName)->first()->id_branch;
        // }


        return inertia('Home/Index', [
            'stats' => $this->services->getStats($month, $year, $branchId),
            'branches' => $isGlobalAdmin ? $allBranches : [],
            'filters' => [
                'month' => (int) $month,
                'year'  => (int) $year,
                'branch' => $branchName
            ]
        ]);
    }
}
