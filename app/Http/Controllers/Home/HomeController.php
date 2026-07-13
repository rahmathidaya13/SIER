<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BranchModel;
use App\Services\Dashboard\DashboardService;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $repository;
    public function __construct(DashboardService $repository)
    {
        $this->repository = $repository;
    }
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);
        $branchName = $request->input('branch', '');

        $branchId = null;

        if (!empty($branchName)) {
            $branch = BranchModel::where('name', $branchName)->first();
            $branchId = $branch ? $branch->id_branch : null;
        }

        // tampilkan semua cabang jika user super admin
        $user = auth()->user();
        $branches = [];
        if ($user->hasRole('super_admin')) {
            $branches = BranchModel::select('id_branch', 'name')->get();
        }
        return inertia('Home/Index', [
            'stats' => $this->repository->getStats($month, $year, $branchId),
            'branches' => $branches,
            'filters' => [
                'month' => (int) $month,
                'year'  => (int) $year,
                'branch' => $branchName
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
