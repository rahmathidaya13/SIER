<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait BranchValidation
{
    public function branchValidation(array $request)
    {
        return Validator::make(
            $request,
            [
                'name' => 'required|string|max:100',
                'address' => 'required|string|max:255',
                'isActive' => 'required|in:active,inactive',
                'status' => 'required|in:official,unofficial',
                'phones' => 'required|array',
                'phones.*.phone' => 'required|string|max:15',
            ],
            [
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
            ]
        )->validate();
    }
}
