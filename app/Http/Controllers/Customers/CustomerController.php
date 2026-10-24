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
use App\Http\Resources\CustomerResource;
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
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:100',
            'order_by' => 'nullable|string|in:oldest,latest',
            'page' => 'nullable|integer|min:1',
        ]);

        $filters = $request->only(array_keys($validated));

        $filters = app(ScopeResolverService::class)->filters($filters); // Gunakan ScopeResolverService untuk memproses filter berdasarkan scope yang diperlukan
        $cleanFilters = Arr::except($filters, ['scope', 'page']); // Hapus 'scope' dan 'page' dari $filters sebelumnya
        $customer = $this->repository->getCached($filters['scope'], $cleanFilters, $filters['page'] ?? 1);

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
        // dd($request->all());
        $user = auth()->user();
        $validated = $this->customerValidation($request->all());
        try {
            $customer = $this->services->store($validated, $user);

            $this->invalidateCaches(
                [
                    CustomerRepository::class
                ],
                [
                    ScopeKey::user($customer->created_by),
                    ScopeKey::branch($customer->created_of_branch),
                    ScopeKey::GLOBAL,
                    ScopeKey::GLOBAL_SUPER_ADMIN,
                ]
            );

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
        return CustomerResource::make($customer);
    }
    public function update(Request $request, string $id)
    {
        $user = auth()->user();
        $customer = CustomerModel::findOrFail($id);

        if (!$user->hasRole(['super_admin', 'developer']) && $customer->created_of_branch !== $user->id_branch) {
            return redirect()->back()->with([
                'error' => 'Anda tidak memiliki izin untuk mengubah data pelanggan ini'
            ]);
        }

        $validated = $this->customerValidation($request->all(), $id);

        $oldUpdatedBy = $customer->updated_by;
        $oldBranchId  = $customer->created_of_branch;
        try {
            $this->services->update($customer, $validated, $user);

            $cacheScopes = [
                ScopeKey::user($user->id),                        // User yang mengedit saat ini
                ScopeKey::user($customer->created_by),            // User pembuat awal
                ScopeKey::branch($oldBranchId),                   // Cabang lama
                ScopeKey::branch($customer->created_of_branch),   // Cabang baru (jika dipindah oleh Super Admin)
                ScopeKey::GLOBAL,
                ScopeKey::GLOBAL_SUPER_ADMIN,
            ];
            // Hanya tambahkan $oldUpdatedBy jika sebelumnya sudah pernah diedit (tidak null)
            if (!empty($oldUpdatedBy)) {
                $cacheScopes[] = ScopeKey::user($oldUpdatedBy);
            }
            $this->invalidateCaches(
                [CustomerRepository::class],
                array_values(array_unique($cacheScopes))
            );
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
        $customer = CustomerModel::findOrFail($id);

        // Simpan data untuk keperluan hapus cache sebelum datanya di-delete
        $CustomerName = $customer->name;
        $cacheScopes = [
            ScopeKey::user($user->id),
            ScopeKey::user($customer->created_by),
            ScopeKey::branch($customer->created_of_branch),
            ScopeKey::GLOBAL,
            ScopeKey::GLOBAL_SUPER_ADMIN,
        ];

        if (!empty($customer->updated_by)) {
            $cacheScopes[] = ScopeKey::user($customer->updated_by);
        }

        try {
            // Eksekusi service delete
            $this->services->delete($customer, $user);

            // Bersihkan cache
            $this->invalidateCaches(
                [CustomerRepository::class],
                array_values(array_unique($cacheScopes))
            );

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
}
