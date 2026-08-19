<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait ProfileValidation
{
    public function validateProfile(array $request)
    {
        return Validator::make(
            $request,
            [
                'name'          => 'required|string|max:50',
                'email'         => 'required|string|email|max:50|unique:users,email,' . auth()->id(),
                'gender'        => 'required|in:L,P',
                'numberPhone'  =>  'required|string|max:15',
                'city'          => 'required|string|max:100',
                'province'      => 'required|string|max:100',
                'address'       => 'required|string|max:255',
                'photo'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ],
            [
                'name.required' => 'Nama tidak boleh kosong',
                'name.string' => 'Nama harus berupa teks',
                'name.max' => 'Nama maksimal 50 karakter',

                'email.required' => 'Email tidak boleh kosong',
                'email.email' => 'Email tidak valid',
                'email.max' => 'Email maksimal 50 karakter',
                'email.unique' => 'Email sudah digunakan',

                'gender.required' => 'Jenis kelamin tidak boleh kosong',
                'gender.in' => 'Jenis kelamin tidak valid',

                'numberPhone.required' => 'Nomor telepon tidak boleh kosong',
                'numberPhone.string' => 'Nomor telepon harus berupa teks',
                'numberPhone.max' => 'Nomor telepon maksimal 15 karakter',

                'city.required' => 'Kota tidak boleh kosong',
                'city.string' => 'Kota harus berupa teks',
                'city.max' => 'Kota maksimal 100 karakter',

                'province.required' => 'Provinsi tidak boleh kosong',
                'province.string' => 'Provinsi harus berupa teks',
                'province.max' => 'Provinsi maksimal 100 karakter',

                'address.required' => 'Alamat tidak boleh kosong',
                'address.string' => 'Alamat harus berupa teks',
                'address.max' => 'Alamat maksimal 255 karakter',


                'photo.image' => 'Foto harus berupa format gambar',
                'photo.max' => 'Ukuran foto maksimal 2MB',
                'photo.mimetypes' => 'Format foto harus JPEG atau PNG',
            ]
        )->validate();
    }
}
