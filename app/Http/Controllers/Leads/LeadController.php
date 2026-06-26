<?php

namespace App\Http\Controllers\Leads;

use App\Models\LeadModel;
use App\Traits\UserScope;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ChannelModel;
use App\Models\ProductModel;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use UserScope;
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
        //
    }




    public function update(Request $request, LeadModel $leadModel)
    {
        //
    }

    public function destroy(LeadModel $leadModel)
    {
        //
    }
}
