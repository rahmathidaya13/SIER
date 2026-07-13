<?php

namespace App\Http\Controllers\Sales;

use App\Traits\UserScope;
use App\Models\SalesModel;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Traits\SalesValidation;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use App\Repository\Lead\LeadsRepository;
use App\Services\Sales\SalePaymentService;
use App\Repository\Channel\ChannelRepository;
use App\Http\Controllers\Sales\SaleRepository;
use App\Services\Sales\PaymentValidationService;
use App\Services\Authorization\ScopeResolverService;

class SaleController extends Controller
{
    use UserScope, BranchMutationHelper, SalesValidation;
    protected $saleRepository;
    protected $scopeResolverService;
    protected $paymentValidationService;
    protected $salePaymentService;

    public function __construct(SaleRepository $saleRepository, ScopeResolverService $scopeResolverService)
    {
        $this->saleRepository = $saleRepository;
        $this->scopeResolverService = $scopeResolverService;
        $this->paymentValidationService = new PaymentValidationService();
        $this->salePaymentService = new SalePaymentService();
    }
    public function index(Request $request)
    {
        $filters = $request->only([
            'keyword',
            'limit',
            'branch',
            'order_by',
            'page',
        ]);

        // inject id_branch and id_user untuk unique cache
        $filters = $this->scopeResolverService->filters($filters);
        $sales = $this->saleRepository->getCached($this->scopeId(auth()->user()), $filters);
        $salesStats = $this->saleRepository->getSalesStats($this->scopeId(auth()->user()), $filters);
        $branch = BranchModel::select('id_branch', 'name')->get();
        return inertia(
            'Sales/Index',
            [
                'sales' => $sales,
                'stats' => $salesStats,
                'filters' => $filters,
                'branch' => $branch
            ]
        );
    }

    public function show(SalesModel $sales)
    {
        $sales->load(['lead', 'user', 'product', 'branch', 'lead.activity.user.roles', 'paymentHistory.user.roles']);
        return response()->json($sales);
    }
    public function payRemaining(Request $request, SalesModel $sales)
    {
        $this->validateSales($request->all());

        $this->paymentValidationService->validateFullPayment($sales, $request['paid_amount']);
        $this->salePaymentService->payRemaining($sales, $request->all());

        $user = auth()->user();
        $this->invalidateCaches([SaleRepository::class, LeadsRepository::class, ChannelRepository::class], ['global', $user->id_branch]);
        return redirect()->back()->with('message', 'Pelunasan berhasil dilakukan');
    }
}
