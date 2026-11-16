<?php

namespace App\Http\Requests;

use App\Models\BranchModel;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    public ?BranchModel $branch = null;
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
        if ($this->isMethod('DELETE')) {
            return [];
        }
        return [
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'isActive' => 'required|in:active,inactive',
            'status' => 'required|in:official,unofficial',
            'phones' => 'required|array',
            'phones.*.phone' => 'required|string|max:15',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama cabang tidak boleh kosong',
            'name.string' => 'Nama cabang harus berupa teks',
            'name.max' => 'Nama cabang maksimal 100 karakter',

            'address.required' => 'Alamat cabang tidak boleh kosong',
            'address.string' => 'Alamat cabang harus berupa teks',
            'address.max' => 'Alamat cabang maksimal 255 karakter',

            'isActive.required' => 'Status cabang harus dipilih',
            'isActive.in' => 'Status cabang tidak valid',

            'status.required' => 'Status cabang tidak boleh kosong',
            'status.in' => 'Status cabang tidak valid',

            'phones.required' => 'Nomor telepon tidak boleh kosong',
            'phones.*.phone.required' => 'Nomor telepon tidak boleh kosong',
            'phones.*.phone.max' => 'Nomor telepon maksimal 15 karakter',
        ];
    }
    public function routeParameters()
    {
        return $this->route('id') ?? $this->route('branch') ?? $this->route('branches');
    }
    public function resolveBranchModel()
    {
        if ($this->branch) {
            return $this->branch;
        }
        $routeParam = $this->routeParameters();
        if ($routeParam instanceof BranchModel) {
            return $this->branch = $routeParam;
        }
        if ($routeParam) {
            return $this->branch = BranchModel::findOrFail($routeParam);
        }
        return null;
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $branch = $this->resolveBranchModel();

            // Validasi Cabang Official (Berlaku saat Tambah / POST maupun Edit / PUT / PATCH)
            if ($this->isMethod('POST') || $this->isMethod('PUT') || $this->isMethod('PATCH')) {
                if ($this->input('status') === 'official') {
                    $officialExists = BranchModel::where('status', 'official')
                        // Saat mode Edit, kecualikan cabang yang sedang diedit agar tidak memblokir dirinya sendiri
                        ->when($branch, fn($query) => $query->where('id_branch', '!=', $branch->id_branch))
                        ->exists();

                    if ($officialExists) {
                        $branchName = $this->input('name') ?? $branch?->name ?? 'ini';
                        $validator->errors()->add(
                            'status',
                            'Tidak dapat menjadikan cabang ' . ucwords($branchName) . ' sebagai cabang resmi karena cabang resmi sudah ada.'
                        );
                    }
                }
            }

            if (!$branch) {
                return;
            }

            $hasActiveUser = $branch->users()->where('is_active', true)->exists();

            // Validasi Non-aktifkan Cabang (PUT / PATCH)
            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {

                // Pastikan cabang asalnya aktif (true/1) DAN request barunya non-aktif (false/0/'0')
                $isDeactivating = (string) ($branch->is_active !== $this->input('isActive'));

                if ($isDeactivating && $hasActiveUser) {
                    $validator->errors()->add(
                        'isActive',
                        'Tidak dapat menonaktifkan cabang ' . ucwords($branch->name) . ' karena masih memiliki pengguna yang aktif.'
                    );
                }
            }

            // Validasi Hapus Cabang (DELETE)
            if ($this->isMethod('DELETE')) {
                if ($hasActiveUser) {
                    $validator->errors()->add(
                        'error',
                        'Tidak dapat menghapus cabang ' . ucwords($branch->name) . ' saat ini masih memiliki pengguna yang aktif.'
                    );
                }
            }
        });
    }
}
