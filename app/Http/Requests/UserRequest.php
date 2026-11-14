<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $actor = $this->user();
        $targetUserId = $this->resolveTargetUserId();
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($targetUserId),
            ],

            'role' => [
                'required',
                Rule::in($this->getAllowedRoles($actor)),
            ],
            // Jika role yang dipilih adalah 'developer', cabang boleh kosong (nullable).
            // Selain itu, jika actor adalah developer / super_admin, cabang wajib dipilih.
            'id_branch' => ($actor->hasAnyRole(['developer', 'super_admin']) && $this->input('role') !== 'developer')
                ? ['required', 'exists:branches,id_branch']
                : ['nullable', 'exists:branches,id_branch'],

            'permissions'   => ['required', 'array'],
            'permissions.*' => ['required', 'exists:permissions,id'],
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Nama tidak boleh kosong.',
            'name.max'      => 'Nama maksimal 100 karakter.',

            'email.required' => 'Email tidak boleh kosong.',
            'email.email'    => 'Email tidak valid.',
            'email.unique'   => 'Email sudah terdaftar.',


            'role.required' => 'Peran tidak boleh kosong.',
            'role.in'       => 'RPeran tidak valid atau Anda tidak memiliki wewenang memilih role tersebut.',

            'id_branch.required' => 'Cabang tidak boleh kosong.',
            'id_branch.exists'   => 'Cabang tidak valid.',

            'permissions.required' => 'Hak Akses wajib dipilih.',
            'permissions.array'    => 'Format Hak Akses tidak valid.',
            'permissions.*.exists' => 'Permission tidak sesuai.',
        ];
    }

    /**
     * Tentukan daftar role yang diizinkan berdasarkan siapa yang sedang login ($actor).
     */
    protected function getAllowedRoles(User $actor): array
    {
        if ($actor->hasRole('developer')) {
            return ['developer', 'super_admin', 'supervisor', 'admin', 'sales', 'teknisi'];
        }

        if ($actor->hasRole('super_admin')) {
            return ['supervisor', 'admin', 'sales', 'teknisi'];
        }

        if ($actor->hasRole('supervisor')) {
            return ['admin', 'sales', 'teknisi'];
        }

        return [];
    }

    /**
     * Ambil ID user yang sedang diedit dari parameter URL route (untuk Rule::unique ignore).
     */
    protected function resolveTargetUserId(): ?string
    {
        // Mengecek parameter route (bisa bernama {id}, {user}, atau {employee} sesuai routes/web.php Anda)
        $param = $this->route('id') ?? $this->route('user') ?? $this->route('employee');

        if ($param instanceof User) {
            return (string) $param->id;
        }

        return $param ? (string) $param : null;
    }

    /**
     * Aturan Bisnis Khusus: 1 Cabang hanya boleh memiliki 1 Supervisor aktif
     * (Berlaku otomatis untuk Store maupun Update)
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Jangan jalankan query jika validasi dasar 'role' atau 'id_branch' saja sudah gagal
            if ($validator->errors()->has('role') || $validator->errors()->has('id_branch')) {
                return;
            }

            $role = $this->input('role');
            $branchId = $this->input('id_branch');

            // Hanya dicek jika role yang dipilih adalah 'supervisor' dan memiliki cabang
            if ($role !== 'supervisor' || empty($branchId)) {
                return;
            }

            $targetUserId = $this->resolveTargetUserId();

            $exists = User::query()
                // Jika sedang update ($targetUserId ada isinya), kecualikan diri sendiri.
                // Jika sedang store ($targetUserId null), cek semua user di cabang tersebut.
                ->when($targetUserId, fn ($q) => $q->whereKeyNot($targetUserId))
                ->where('id_branch', $branchId)
                ->where('is_active', true)
                ->role('supervisor')
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'role',
                    'Cabang ini sudah memiliki Supervisor aktif. Nonaktifkan atau ubah role Supervisor yang ada terlebih dahulu.'
                );
            }
        });
    }
}
