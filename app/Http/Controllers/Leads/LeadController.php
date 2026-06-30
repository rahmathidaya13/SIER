<?php

namespace App\Http\Controllers\Leads;

use App\Models\LeadModel;
use App\Traits\UserScope;
use App\Models\ChannelModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Traits\LeadsValidation;
use App\Http\Controllers\Controller;
use App\Services\LeadServices;
use App\Traits\BranchMutationHelper;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use UserScope, BranchMutationHelper, LeadsValidation;
    protected $leadRepository;
    protected $leadServices;
    public function __construct(LeadsRepository $leadRepository, LeadServices $leadServices)
    {
        $this->leadRepository = $leadRepository;
        $this->leadServices = $leadServices;
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        $filters = $request->only([
            'keyword',
            'limit',
            'temperature',
            'status',
            'order_by',
            'page',

        ]);
        $leads = $this->leadRepository->getCached($this->scopeId($user), $filters);
        $channels = ChannelModel::select('id_channel', 'name', 'icon')->get();
        $products = ProductModel::select('id_product', 'name')->get();
        return inertia(
            'Leads/Index',
            [
                'leads' => $leads,
                'filters' => $filters,
                'channels' => $channels,
                'products' => $products,
            ]
        );
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $this->validateLeads($request->all());
        $this->leadServices->store($request->all(), $user);
        $this->invalidateCaches(LeadsRepository::class, ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Lead berhasil ditambahkan');
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $this->validateLeads($request->all(), $id);
        $lead = $this->leadServices->update($id, $request->all(), $user);
        $this->invalidateCaches(LeadsRepository::class, ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Perubahan data dari lead pelanggan '.$lead->name.' berhasil diperbarui');
    }

    public function show(string $id)
    {
        $leads = LeadModel::allowedForUser(auth()->user())
            ->with([
                'user:id,name as creator_name',
                'channel:id_channel,name as channel_name,icon',
                'product:id_product,name as product_name',
                'activity.user:id,name as activity_creator_name',
                'user.roles:id,name',
            ])
            ->findOrFail($id);
        return response()->json($leads);
    }
    public function destroy(LeadModel $leadModel, string $id)
    {
        //
    }
}
