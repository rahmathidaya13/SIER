<?php

namespace App\Http\Controllers\Leads;

use App\Models\LeadModel;
use App\Traits\UserScope;
use App\Models\ChannelModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use UserScope, BranchMutationHelper;
    protected $leadRepository;
    public function __construct(LeadsRepository $leadRepository)
    {
        $this->leadRepository = $leadRepository;
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
                'products' => $products
            ]
        );
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $leads = new LeadModel();
        $leads->id_user = $user->id;
        $leads->id_channel = $request['id_channel'];
        $leads->id_product = $request['id_product'];
        $leads->id_branch = $user->id_branch;
        $leads->name = $request['name'];
        $leads->email = $request['email'];
        $leads->phone = $request['phone'];
        $leads->status = $request['status'];
        $leads->follow_up_date = $request['follow_up_date'];
        $leads->temperature = $request['temperature'];
        $leads->notes = $request['notes'];
        $leads->save();
        $this->invalidateCaches(LeadsRepository::class, ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Lead berhasil ditambahkan');
    }




    public function update(Request $request, LeadModel $leadModel)
    {


    }

    public function destroy(LeadModel $leadModel)
    {
        //
    }
}
