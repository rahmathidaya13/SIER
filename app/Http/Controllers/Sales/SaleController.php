<?php

namespace App\Http\Controllers\Sales;

use App\Services\Sales\PaymentCancelled;
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
    protected $paymentCancelledService;

    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository = $saleRepository;
        $this->paymentValidationService = new PaymentValidationService();
        $this->salePaymentService = new SalePaymentService();
        $this->paymentCancelledService = new PaymentCancelled();
    }
    public function index(Request $request)
    {

        $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
            'branch' => 'nullable|string|in:all,' . implode(',', BranchModel::pluck('name')->toArray()),
            'status' => 'nullable|string|in:all,closing,cancelled'
        ]);

        $filters = $request->only([
            'keyword',
            'limit',
            'branch',
            'order_by',
            'page',
            'status'
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
        $sales = SalesModel::query()
            ->select([
                'id_sales',
                'invoice',
                'id_lead',
                'id_user',
                'id_branch',
                'deal_price',
                'paid_amount',
                'remaining_bill',
                'quantity',
                'payment_status',
                'status',
                'deal_at',
                'notes',
                'cancelled_at',
                'cancelled_by',
                'cancelled_reason',
                'cancelled_notes',
                'created_at',
                'updated_at',
            ])
            ->with([
                'lead' => function ($query) {
                    $query->select([
                        'id_lead',
                        'name',
                        'phone',
                    ]);
                },

                // User Sales
                'user' => function ($query) {
                    $query->select([
                        'id',
                        'name',
                    ]);
                },
                'user.roles:id,name',
                'branch' => function ($query) {
                    $query->select([
                        'id_branch',
                        'name',
                    ]);
                },
                'lead.activity' => function ($query) {
                    $query
                        ->select([
                            'id_lead_activity',
                            'id_lead',
                            'id_user',
                            'description',
                            'created_at',
                        ])
                        ->latest('created_at');
                },
                'lead.activity.user:id,name',
                'lead.activity.user.roles:id,name',

                'paymentHistory' => function ($query) {
                    $query
                        ->select([
                            'id_payment_history',
                            'id_sales',
                            'id_user',
                            'payment_method',
                            'bank_name',
                            'paid_amount',
                            'payment_at',
                            'notes_payment',
                            'created_at',
                        ])
                        ->latest('created_at');
                },

                'paymentHistory.user:id,name',
                'paymentHistory.user.roles:id,name',

                'salesProduct' => function ($query) {
                    $query->select([
                        'id_sales',
                        'id_product',
                    ]);
                },

                'salesProduct.product:id_product,name',
                'cancelledBy:id,name',
                'cancelledBy.roles:id,name',
            ])
            ->findOrFail($id);

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

    public function payCancel(Request $request, string $id)
    {
        $user = auth()->user();
        $validated = $this->validateSales($request->all(), 'payment_cancel');

        $sales = SalesModel::find($id);
        $this->paymentCancelledService->__invoke($sales, $validated);
        $this->invalidateCaches(
            [
                SaleRepository::class,
                LeadsRepository::class,
                ChannelRepository::class
            ],
            [
                'global',
                'global_super_admin',
                'branch_' . $user->id_branch,
                'user_' . $user->id
            ]
        );
        return redirect()->back()->with('message', 'Closing berhasil dibatalkan');
    }
}
