<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Channel\ChannelRepository;
use App\Models\LeadModel;
use App\Traits\UserScope;
use App\Models\ChannelModel;
use App\Models\ProductModel;
use Illuminate\Http\Request;
use App\Services\LeadServices;
use App\Traits\LeadsValidation;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use App\Services\LeadConversionServices;
use App\Http\Controllers\Sales\SaleRepository;
use Illuminate\Validation\ValidationException;
use App\Services\Authorization\ScopeResolverService;
use App\Traits\ActivityMessageFormatter;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use UserScope, BranchMutationHelper, LeadsValidation, ActivityMessageFormatter;
    protected $leadRepository;
    protected $leadServices;
    protected $leadConversionServices;
    protected $scopeResolverService = ScopeResolverService::class;
    public function __construct(LeadsRepository $leadRepository, LeadServices $leadServices, LeadConversionServices $leadConversionServices)
    {
        $this->leadRepository = $leadRepository;
        $this->leadServices = $leadServices;
        $this->leadConversionServices = $leadConversionServices;
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

        $filters = app(ScopeResolverService::class)->filters($filters);

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

        if ($request['status'] === 'CLOSED_WON' && $request['payment_status'] === 'DP') {
            $minimalDp = $request['deal_price'] * 0.5;
            if ($request['paid_amount'] < $minimalDp) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Uang muka (DP) minimal harus 50% (Minimal: Rp ' . number_format($minimalDp, 0, ',', '.') . ')'
                ]);
            }
        }

        $this->leadServices->store($request->all(), $user);

        $this->invalidateCaches([LeadsRepository::class, SaleRepository::class, ChannelRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with([
            'message' => 'Lead dari ' . strtoupper($request['name']) . ' berhasil ditambahkan',
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $this->validateLeads($request->all(), $id);
        $lead = $this->leadServices->update($id, $request->all(), $user);
        $this->invalidateCaches([LeadsRepository::class, SaleRepository::class, ChannelRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with('message', $this->msgLeadUpdated($request->only(['status', 'temperature'])));
    }

    public function show(string $id)
    {
        $leads = LeadModel::allowedForUser(auth()->user())
            ->with([
                'user:id,name as creator_name',
                'channel:id_channel,name as channel_name,icon',
                'product:id_product,name as product_name',
                'activity.user:id,name as activity_creator_name',
                'activity.user.roles:id,name',
                'activity.userUpdate.roles:id,name as updated_by_name',
            ])
            ->findOrFail($id);
        return response()->json($leads);
    }
    public function destroy(LeadModel $leads)
    {
        $this->leadServices->delete($leads);
        $this->invalidateCaches([LeadsRepository::class, SaleRepository::class, ChannelRepository::class], ['global', $leads->id_branch]);
        return redirect()->back()->with('message', 'Lead ' . $leads->name . ' berhasil dihapus');
    }
}
