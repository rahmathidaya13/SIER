<?php

namespace App\Http\Controllers\Sales;

use App\Traits\UserScope;
use App\Models\SalesModel;
use App\Models\BranchModel;
use Illuminate\Http\Request;
use App\Traits\SalesValidation;
use App\Http\Controllers\Controller;
use App\Traits\BranchMutationHelper;
use App\Repository\Sale\SaleRepository;
use App\Repository\Lead\LeadsRepository;
use App\Services\Sales\SalePaymentService;
use App\Repository\Channel\ChannelRepository;
use App\Services\Sales\PaymentValidationService;
use App\Services\Authorization\ScopeResolverService;

class SaleController extends Controller
{
    use UserScope, BranchMutationHelper, SalesValidation;
    protected $saleRepository;
    protected $paymentValidationService;
    protected $salePaymentService;

    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
        $this->paymentValidationService = new PaymentValidationService();
        $this->salePaymentService = new SalePaymentService();
    }
    public function index(Request $request)
    {

        $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
            'branch' => 'nullable|string|in:all,' . implode(',', BranchModel::pluck('name')->toArray()),
        ]);

        $filters = $request->only([
            'keyword',
            'limit',
            'branch',
            'order_by',
            'page',
        ]);

        // inject id_branch and id_user untuk unique cache
        $filters = app(ScopeResolverService::class)->filters($filters);
        $sales = $this->saleRepository->getCached($filters['scope'], $filters);

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

    public function show(string $id)
    {
        $sales = SalesModel::with(
            [
                'lead',
                'user',
                'branch',
                'lead.activity.user.roles',
                'paymentHistory.user.roles',
                'salesProduct.product:id_product,name,image_link,image_path'
            ]
        )->find($id);
        return response()->json($sales);
    }
    public function payRemaining(Request $request, string $id)
    {
        $this->validateSales($request->all());

        $sales = SalesModel::find($id);

        $this->paymentValidationService->validateFullPayment($sales, $request['paid_amount']);
        $this->salePaymentService->payRemaining($sales, $request->all());

        $user = auth()->user();
        $this->invalidateCaches(
            [SaleRepository::class, LeadsRepository::class, ChannelRepository::class],
            ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]
        );
        return redirect()->back()->with('message', 'Pelunasan berhasil dilakukan');
    }

    public function cancelClosing()
    {
        $user = auth()->user();
        $this->invalidateCaches(
            [SaleRepository::class, LeadsRepository::class, ChannelRepository::class],
            ['global', 'global_super_admin', 'branch_' . $user->id_branch, 'user_' . $user->id]
        );
        return redirect()->back()->with('message', 'Closing berhasil dibatalkan');
    }
}
