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
    public function store(Request $request)
    {
        $this->authorize('create', CustomerModel::class, 'unauthorized_action');

        // dd($request->all());
        $user = auth()->user();
        $validated = $this->customerValidation($request->all());
        try {
            $customer = $this->services->store($validated, $user);

            $this->clearCustomerCaches($customer);

            return redirect()->back()->with([
                'message' => 'Pelanggan baru ' . strtoupper($validated['name']) . ' berhasil ditambahkan',
            ]);
        } catch (\Exception $error) {
            $errorMessage = config('app.debug') ? $error->getMessage() : 'Terjadi kesalahan pada server. Silakan coba lagi.';
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $errorMessage]);
        }
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
            ->with(['branch', 'creator', 'updater'])
            ->findOrFail($id);

        $this->authorize('view', $customer); // Memeriksa izin menggunakan CustomerPolicy

        return CustomerResource::make($customer);
    }
    public function update(Request $request, string $id)
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

        if (!$user->hasRole(['super_admin', 'developer']) && $customer->created_of_branch !== $user->id_branch) {
            return redirect()->back()->with([
                'error' => 'Anda tidak memiliki izin untuk mengubah data pelanggan ini'
            ]);
        }

        $validated = $this->customerValidation($request->all(), $id);

        $oldBranchId  = $customer->getOriginal('created_of_branch');
        $oldUpdatedBy = $customer->getOriginal('updated_by');
        try {
            $this->services->update($customer, $validated, $user);

            $this->clearCustomerCaches($customer, $user->id, $oldBranchId, $oldUpdatedBy);
            return redirect()->back()->with([
                'message' => 'Pelanggan ' . strtoupper($validated['name']) . ' berhasil diperbarui',
            ]);
        } catch (\Exception $error) {
            $errorMessage = config('app.debug') ? $error->getMessage() : 'Terjadi kesalahan pada server. Silakan coba lagi.';
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $errorMessage]);
        }
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

        $this->authorize('delete', $customer);

        if ($customer->leads()->exists() || $customer->sales()->exists()) {
            throw ValidationException::withMessages([
                'error' => 'Pelanggan ' . ucwords($customer->name) . ' tidak dapat dihapus karena sudah memiliki riwayat leads atau transaksi.'
            ]);
        }

        // Simpan data untuk keperluan hapus cache sebelum datanya di-delete
        $CustomerName = $customer->name;
        try {
            // Eksekusi service delete
            $this->services->delete($customer, $user);

            $this->clearCustomerCaches($customer, $user->id);

            return redirect()->back()->with([
                'message' => 'Pelanggan ' . strtoupper($CustomerName) . ' berhasil dihapus.',
            ]);
        } catch (ValidationException $error) {
            // Biarkan pesan larangan hapus (karena sudah ada di Lead/Sales) tampil ke user
            throw $error;
        } catch (\Exception $error) {
            $errorMessage = config('app.debug')
                ? $error->getMessage()
                : 'Terjadi kesalahan pada server. Silakan coba lagi.';

            return redirect()->back()->withErrors(['error' => $errorMessage]);
        }
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
