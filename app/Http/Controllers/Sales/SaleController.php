<?php

namespace App\Http\Controllers\Sales;

use App\Traits\UserScope;
use App\Models\SalesModel;
use App\Models\BranchModel;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\PaymentHistory;
use App\Traits\SalesValidation;
use App\Models\LeadActivityModel;
use App\Traits\InvalidatesCaches;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesRequest;
use App\Http\Resources\SalesResource;
use App\Repository\Sale\SaleRepository;
use App\Repository\Lead\LeadsRepository;
use App\Services\Sales\SalePaymentService;
use App\Http\Resources\LeadActivityResource;
use App\Repository\Channel\ChannelRepository;
use App\Http\Resources\PaymentHistoriesResource;
use App\Services\Authorization\ScopeResolverService;
use App\Support\ScopeKey;

class SaleController extends Controller
{
    use UserScope, SalesValidation, InvalidatesCaches;
    protected $saleRepository;
    protected $paymentServices;

    public function __construct(SaleRepository $saleRepository)
    {
        $this->saleRepository           = $saleRepository;
        $this->paymentServices      = new SalePaymentService();
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', SalesModel::class);

        $branch     = BranchModel::select('id_branch', 'name')->get();
        $allowedBranchNames = $branch->pluck('name')->implode(',');

        $validated = $request->validate([
            'keyword'  => 'nullable|string|max:255',
            'limit'    => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page'     => 'nullable|integer|min:1',
            'branch'   => 'nullable|string|in:all,' . ($allowedBranchNames ? ',' . $allowedBranchNames : ''),
            'status'   => 'nullable|string|in:all,closing,cancelled',
        ]);

        $filters = $request->only(array_keys($validated));
        $filters = app(ScopeResolverService::class)->filters($filters);

        $sales   = $this->saleRepository->getCached($filters['scope'], $filters, $filters['page'] ?? 1);

        $salesStats = $this->saleRepository->getSalesStats($filters['scope'], $filters);
        return inertia(
            'Sales/Index',
            [
                'sales'   => $sales,
                'stats'   => $salesStats,
                'filters' => $filters,
                'branch'  => $branch,
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
                'id_customer',
                'id_branch',
                'created_by',
                'updated_by',
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
                'creator:id,name',
                'updater:id,name',
                'creator.roles:id,name',
                'updater.roles:id,name',
                'customer:id_customer,name,phone,email',

                'salesProduct:id_sales,id_product',
                'salesProduct.product:id_product,name',
                'cancelledBy:id,name',
                'cancelledBy.roles:id,name',
            ])
            ->withCount('salesProduct')
            ->findOrFail($id);

        return SalesResource::make($sales);
    }

    public function activities(string $id)
    {
        $sales = SalesModel::query()
            ->select([
                'id_sales',
                'id_lead',
            ])
            ->findOrFail($id);

        $activities = LeadActivityModel::query()
            ->where('id_lead', $sales->id_lead)
            ->select([
                'id_lead_activity',
                'id_lead',
                'created_by',
                'updated_by',
                'description',
                'source',
                'created_at',
                'updated_at',
            ])
            ->with([
                'creator:id,name',
                'creator.roles:id,name',
                'userUpdate:id,name',
                'userUpdate.roles:id,name',
            ])
            ->latest('created_at')
            ->paginate(10);
        return LeadActivityResource::collection($activities);
    }
    public function paymentHistory(string $id)
    {
        $sales = SalesModel::query()
            ->select([
                'id_sales',
                'id_lead',
            ])
            ->findOrFail($id);

        $payHistory = PaymentHistory::query()
            ->where('id_lead', $sales->id_lead)
            ->select([
                'id_sales',
                'created_by',
                'updated_by',
                'id_customer',
                'id_lead',
                'payment_method',
                'bank_name',
                'paid_amount',
                'payment_at',
                'notes_payment',
                'created_at',
                'updated_at',
            ])
            ->with([
                'creator:id,name',
                'creator.roles:id,name',
                'updater:id,name',
                'updater.roles:id,name',
            ])
            ->latest('created_at')
            ->paginate(2);
        return PaymentHistoriesResource::collection($payHistory);
    }
    public function payRemaining(SalesRequest $request, string $id)
    {
        $validated = $request->validated();
        $user = auth()->user();

        $sales = $request->resolveSalesModel();

        $this->authorize('payment', $sales, 'unauthorized_action');

        $this->paymentServices->payRemaining($sales, $validated);

        $this->clearSalesCaches($sales, $user, false);
        return redirect()->back()->with('message', 'Pelunasan dari pelanggan ' . ucwords($sales->customer->name) . ' berhasil dilakukan');
    }

    public function payCancel(SalesRequest $request, string $id)
    {
        $validated = $request->validated();
        $user      = auth()->user();

        $sales = $request->resolveSalesModel();

        $this->authorize('paymentCancel', $sales, 'unauthorized_action');

        $this->paymentServices->payCancelled($sales, $validated);
        $this->clearSalesCaches($sales, $user);
        return redirect()->back()->with('message', 'Pembatalan Transaksi dari pelanggan ' . ucwords($sales->customer->name) . '  berhasil dilakukan');
    }

    protected function clearSalesCaches(SalesModel $sales, $user, bool $includeRelated = true): void
    {
        $scopes = array_values(array_unique(array_filter([
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
            $user?->id ? ScopeKey::user($user->id) : null,
            $sales->created_by ? ScopeKey::user($sales->created_by) : null,
            $sales->updated_by ? ScopeKey::user($sales->updated_by) : null,
            $user?->id_branch ? ScopeKey::branch($user->id_branch) : null,
            $sales->id_branch ? ScopeKey::branch($sales->id_branch) : null,
        ])));
        $repositories = $includeRelated
            ? [SaleRepository::class, LeadsRepository::class, ChannelRepository::class]
            : [SaleRepository::class];
        $this->invalidateCaches($repositories, $scopes);
    }
}
