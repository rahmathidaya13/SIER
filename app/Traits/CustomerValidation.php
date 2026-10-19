<?php

namespace App\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

trait CustomerValidation
{
    public function customerValidation($request, $id = null)
    {
        return Validator::make(
            $request,
            [
                'name' => 'required|string|max:150',
                'pic_name' => 'nullable|string|max:100',
                'phone' => 'required|string|max:15',
                'email' => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')->ignore($id, 'id_customer')],
                'tax_number' => 'nullable|string|max:30',
                'customer_type' => 'required|in:individual,company,government',
                'customer_tag' => 'required|in:new,repeat,has_cancelled',
                'business_type' => 'required|in:retail,fnb,service,manufacture,other',
                'status' => 'required|in:active,inactive,blacklisted',
                'is_member' => 'required|boolean',
                'city' => 'required|string|max:100',
                'address' => 'required|string|max:255',
                'created_of_branch' => 'required|exists:branches,id_branch',
            ],
            [
                'name.required' => 'Nama Pelanggan tidak boleh kosong',
                'name.string' => 'Nama Pelanggan harus berupa teks',
                'name.max' => 'Nama Pelanggan maksimal 150 karakter',

                'pic_name.string' => 'Nama penanggung jawab harus berupa teks',
                'pic_name.max' => 'Nama penanggung jawab maksimal 100 karakter',

                'phone.required' => 'Nomor telepon tidak boleh kosong',
                'phone.string' => 'Nomor telepon harus berupa angka',
                'phone.max' => 'Nomor telepon maksimal 15 karakter',

                'email.required' => 'Email tidak boleh kosong',
                'email.string' => 'Email harus berupa teks',
                'email.email' => 'Format email tidak sesuai',
                'email.max' => 'Email maksimal 150 karakter',
                'email.unique' => 'Email sudah terdaftar',

                'tax_number.string' => 'Nomor Pajak harus berupa angka',
                'tax_number.max' => 'Nomor Pajak maksimal 30 karakter',

                'status.required' => 'Status harus dipilih',
                'status.in' => 'Status tidak sesuai',

                'is_member.required' => 'Member wajib dipilih',
                'is_member.boolean' => 'Member tidak sesuai',

                'city.required' => 'Kota tidak boleh kosong',
                'city.string' => 'Kota harus berupa teks',
                'city.max' => 'Kota maksimal 100 karakter',

                'customer_type.required' => 'Tipe pelanggan wajib dipilih',
                'customer_type.in' => 'Tipe pelanggan tidak sesuai',

                'business_type.required' => 'Sektor bisnis wajib dipilih',
                'business_type.in' => 'Sektor bisnis tidak sesuai',

                'customer_tag.required' => 'Kategori pelanggan wajib dipilih',
                'customer_tag.in' => 'Kategori pelanggan tidak sesuai',

                'address.required' => 'Alamat tidak boleh kosong',
                'address.string' => 'Alamat harus berupa teks',
                'address.max' => 'Alamat maksimal 255 karakter',

                'created_of_branch.required' => 'Cabang tidak boleh kosong',
                'created_of_branch.exists' => 'Cabang tidak ditemukan',
            ]
        )->validate();
    }
}
