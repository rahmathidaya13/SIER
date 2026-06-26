<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use App\Models\LeadModel;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'keyword',
            'limit',
            'temperature',
            'status',
            'order_by',
            'page',

        ]);
        $leads = LeadModel::allowedForUser()
            ->with([
                'user:id,name',
                'channel:id_channel,name,icon',
                'product:id_product,name'
            ])
            ->when(isset($filters['keyword']), function ($query, $filters) {

                $search = $filters['keyword'] ?? '';

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['status']), function ($query, $filters) {
                $query->where('status', $filters['status'] ?? null);
            })
            ->when(isset($filters['temperature']), function ($query, $filters) {
                $query->where('temperature', $filters['temperature'] ?? null);
            })
            ->latest()
            ->paginate($filters['limit'] ?? 10)
            ->withQueryString();
        return inertia(
            'Leads/Index',
            [
                'leads' => $leads,
            ]
        );
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
    public function show(LeadModel $leadModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeadModel $leadModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeadModel $leadModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeadModel $leadModel)
    {
        //
    }
}
