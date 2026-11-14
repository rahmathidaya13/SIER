<?php

namespace App\Http\Controllers\Customers;

use App\Support\ScopeKey;
use App\Models\BranchModel;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\CustomerModel;
use App\Traits\InvalidatesCaches;
use App\Traits\CustomerValidation;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use Illuminate\Support\Facades\Gate;
use App\Repository\Sale\SaleRepository;
use App\Repository\User\UserRepository;
use App\Http\Resources\CustomerResource;
use App\Repository\Lead\LeadsRepository;
use App\Services\Customers\CustomerService;
use Illuminate\Validation\ValidationException;
use App\Repository\Customers\CustomerRepository;
use App\Services\Authorization\ScopeResolverService;

class CustomerController extends Controller
{
    use InvalidatesCaches, CustomerValidation;
    protected $repository;
    protected $services;
    public function __construct()
    {
        $this->repository = new CustomerRepository();
        $this->services = new CustomerService();
    }
    public function index(Request $request)
    {
        $this->authorize('viewAny', CustomerModel::class);

        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
            'branch' => 'nullable|string|in:all,' . implode(',', BranchModel::pluck('name')->toArray()),
        ]);

        $filters = $request->only(array_keys($validated));

        $filters = app(ScopeResolverService::class)->filters($filters); // Gunakan ScopeResolverService untuk memproses filter berdasarkan scope yang diperlukan
        $customer = $this->repository->getCached($filters['scope'], $filters, $filters['page'] ?? 1);
        $branch = BranchModel::select('id_branch', 'name')->get();
        return inertia(
            'Customers/Index',
            [
                'customers' => $customer,
                'filters' => $filters,
                'branches' => $branch
            ]
        );
    }
    public function store(CustomerRequest $request)
    {
        $this->authorize('create', CustomerModel::class, 'unauthorized_action');

        $user = auth()->user();

        $validated = $request->validated();

        $customer = $this->services->store($validated, $user);

        $this->clearCustomerCaches($customer, $user->id);

        return redirect()->back()->with([
            'message' => 'Pelanggan baru ' . strtoupper($validated['name']) . ' berhasil ditambahkan',
        ]);
    }
    public function show(string $id)
    {
        $customer = CustomerModel::query()
            ->select([
                'id_customer',
                'created_by',
                'updated_by',
                'created_of_branch',
                'name',
                'customer_code',
                'pic_name',
                'phone',
                'email',
                'tax_number',
                'customer_type',
                'customer_tag',
                'business_type',
                'status',
                'is_member',
                'city',
                'address',
            ])
            ->findOrFail($id);

        $this->authorize('view', $customer); // Memeriksa izin menggunakan CustomerPolicy

        return CustomerResource::make($customer);
    }
    public function update(CustomerRequest $request, string $id)
    {
        $user = auth()->user();
        $customer = CustomerModel::with([
            // Load  relasi 'creator' & 'updater' (withTrashed) agar Policy tidak terkena N+1 query saat cek role pembuat
            'creator' => fn($q) => $q->withTrashed(),
            'updater' => fn($q) => $q->withTrashed()
        ])
            ->findOrFail($id);

        // Memeriksa izin menggunakan CustomerPolicy
        $this->authorize('update', $customer, 'unauthorized_action');

        $validated = $request->validated();

        $oldBranchId  = $customer->getOriginal('created_of_branch');
        $oldUpdatedBy = $customer->getOriginal('updated_by');

        $this->services->update($customer, $validated, $user);

        $this->clearCustomerCaches(
            $customer,
            $user->id,
            $oldBranchId,
            $oldUpdatedBy
        );
        return redirect()->back()->with([
            'message' => 'Pelanggan ' . strtoupper($validated['name']) . ' berhasil diperbarui',
        ]);
    }

    public function destroy(string $id)
    {
        $user = auth()->user();
        $customer = CustomerModel::with([
            // Load  relasi 'creator' & 'updater' (withTrashed) agar Policy tidak terkena N+1 query saat cek role pembuat
            'creator' => fn($q) => $q->withTrashed(),
            'updater' => fn($q) => $q->withTrashed()
        ])
            ->findOrFail($id);

        // Memeriksa izin menggunakan CustomerPolicy
        $this->authorize('delete', $customer);

        // Simpan data untuk keperluan hapus cache sebelum datanya di-delete
        $CustomerName = $customer->name;
        $this->services->delete($customer, $user);

        $this->clearCustomerCaches($customer, $user->id);

        return redirect()->back()->with([
            'message' => 'Pelanggan ' . strtoupper($CustomerName) . ' berhasil dihapus.',
        ]);
    }

    protected function clearCustomerCaches(
        CustomerModel $customer,
        ?string $actorUserId = null,
        ?string $oldBranchId = null,
        ?string $oldUpdatedBy = null
    ) {
        $cacheScopes = [
            $actorUserId ? ScopeKey::user($actorUserId) : null, // User yang mengedit/menghapus saat ini
            ScopeKey::user($customer->created_by), // User pembuat awal
            $customer->updated_by ? ScopeKey::user($customer->updated_by) : null, // User pengedit terakhir di DB
            $oldUpdatedBy ? ScopeKey::user($oldUpdatedBy) : null,         // User pengedit sebelumnya (saat update)
            $oldBranchId ? ScopeKey::branch($oldBranchId) : null, // Cabang lama (jika pindah cabang)
            ScopeKey::branch($customer->created_of_branch), // Cabang saat ini
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ];
        $this->invalidateCaches(
            [
                CustomerRepository::class,
                LeadsRepository::class,
                SaleRepository::class,
            ],
            $cacheScopes
        );
    }
}
