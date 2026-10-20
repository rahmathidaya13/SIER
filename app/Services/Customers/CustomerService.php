<?php

namespace App\Services\Customers;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\CustomerModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function store(array $validated, User $user)
    {
        DB::beginTransaction();
        try {
            $branchId = $this->resolveBranchId($validated, $user);
            $customer = CustomerModel::create([
                'created_by' => $user->id,
                'created_of_branch' => $branchId,
                'customer_code'     => $this->generateUniqueCustomerCode(),
                'name'              => $validated['name'],
                'pic_name'          => $this->nullIfEmpty($validated['pic_name'] ?? null),
                'email'             => $this->nullIfEmpty($validated['email'] ?? null),
                'tax_number'        => $this->nullIfEmpty($validated['tax_number'] ?? null),
                'phone'             => $validated['phone'],
                'address'           => $validated['address'],
                'customer_type'     => $validated['customer_type'],
                'business_type'     => $validated['business_type'],
                'customer_tag'      => $validated['customer_tag'] ?? 'new',
                'is_member'         => (bool) ($validated['is_member'] ?? false),
                'status'            => $validated['status'] ?? 'active',
                'city'              => $this->nullIfEmpty($validated['city'] ?? null),
            ]);

            DB::commit();
            return $customer;
        } catch (\Exception $error) {
            DB::rollBack(); // JIKA ADA ERROR (Server mati, database putus, dll), BATALKAN SEMUA!
            Log::error("Gagal menyimpan pelaggan baru: " . $error);
            throw $error;
        }
    }

    public function update(CustomerModel $customer, array $validated, User $user)
    {
        DB::beginTransaction();
        try {
            // Hanya super_admin/dev yang boleh mengubah cabang pelanggan saat edit.
            // Jika user biasa yang edit, pertahankan cabang asli pelanggan tersebut.
            $branchId = $this->canManageAllBranches($user)
                ? ($validated['created_of_branch'] ?? $customer->created_of_branch)
                : $customer->created_of_branch;

            $customer->update([
                'updated_by' =>  $user->id,
                'created_of_branch' => $branchId,
                'name' => $validated['name'],
                'pic_name'          => $this->nullIfEmpty($validated['pic_name'] ?? null),
                'email'             => $this->nullIfEmpty($validated['email'] ?? null),
                'tax_number'            => $this->nullIfEmpty($validated['tax_number'] ?? null),
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'customer_type' => $validated['customer_type'],
                'customer_tag'      => $validated['customer_tag'] ?? 'new',
                'business_type' => $validated['business_type'],
                'status'            => $validated['status'] ?? $customer->status,
                'is_member'         => (bool) ($validated['is_member'] ?? false),
                'city'              => $this->nullIfEmpty($validated['city'] ?? null),
            ]);
            DB::commit();
            return $customer;
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal memperbarui pelanggan [{$customer->id_customer}]: " . $error->getMessage());
            throw $error;
        }
    }

    public function delete(CustomerModel $customer, User $user): bool
    {
        // 1. Cek apakah pelanggan sudah pernah bertransaksi (Sales)
        if ($customer->sales()->exists()) {
            throw ValidationException::withMessages([
                'error' => "Pelanggan {$customer->name} tidak dapat dihapus karena sudah memiliki riwayat transaksi.",
            ]);
        }

        // 2. Cek apakah pelanggan sudah masuk ke dalam prospek (Lead)
        if ($customer->leads()->exists()) {
            throw ValidationException::withMessages([
                'error' => "Pelanggan {$customer->name} tidak dapat dihapus karena sudah terdaftar di dalam data Prospek (Lead).",
            ]);
        }

        DB::beginTransaction();
        try {
            // Catat siapa yang melakukan penghapusan sebelum soft-delete
            $customer->update([
                'updated_by' => $user->id,
            ]);

            $customer->delete();

            DB::commit();
            return true;
        } catch (\Exception $error) {
            DB::rollBack();
            Log::error("Gagal menghapus pelanggan [{$customer->id_customer}]: " . $error->getMessage());
            throw $error;
        }
    }

    /**
     * Generate kode unik pelanggan (Contoh: CUST-2610-X9B2).
     */
    private function generateUniqueCustomerCode(): string
    {
        $prefix = 'CUST-' . now()->format('ym') . '-';

        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (CustomerModel::withTrashed()->where('customer_code', $code)->exists());

        return $code;
    }

    /**
     * Ubah string kosong "" menjadi null agar aman di database.
     */
    private function nullIfEmpty(?string $value): ?string
    {
        $trimmed = is_string($value) ? trim($value) : null;
        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Helper untuk menentukan ID Cabang yang valid saat pembuatan data.
     */
    private function resolveBranchId(array $validated, User $user): string
    {
        if ($this->canManageAllBranches($user) && isset($validated['created_of_branch'])) {
            return $validated['created_of_branch'];
        }

        return $user->id_branch;
    }

    /**
     * Cek apakah user memiliki hak akses lintas cabang.
     */
    private function canManageAllBranches(User $user): bool
    {
        return $user->hasRole(['super_admin', 'developer']);
    }
}
