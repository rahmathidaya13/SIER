<?php

namespace App\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

trait RegisterValidation
{
    public function validateRegister(array $data)
    {
        return Validator::make(
            $data,
            [
                'name' => ['required', 'string', 'max:100'],
                'email' => ['required', 'string', 'email', 'max:100', Rule::unique('users', 'email')],
                'role' => ['required', 'in:supervisor,admin,sales,teknisi'],
                'id_branch' => ['required', 'exists:branches,id_branch'],
                'branch_code'           => ['required', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ],
            [

                'name.required' => 'Nama tidak boleh kosong',
                'name.string' => 'Nama harus berupa teks',
                'name.max' => 'Nama maksimal 100 karakter',

                'email.required' => 'Email tidak boleh kosong',
                'email.string' => 'Email harus berupa teks yang jelas',
                'email.email' => 'Email tidak valid',
                'email.max' => 'Email maksimal 100 karakter',
                'email.unique' => 'Email sudah terdaftar',

                'password.required' => 'Kata sandi tidak boleh kosong',
                'password.string' => 'Kata sandi harus berupa teks',
                'password.min' => 'Kata sandi minimal 8 karakter',
                'password.confirmed' => 'Kata sandi yang diulangi tidak cocok',

                'role.required' => 'Role wajib dipilih',
                'role.in' => 'Role tidak tersedia',

                'id_branch.required' => 'Cabang tidak boleh kosong',
                'id_branch.exists' => 'Cabang tidak tersedia',

                'branch_code.required' => 'Kode Cabang tidak boleh kosong',
                'branch_code.string' => 'Kode Cabang harus berupa teks',
            ]
        )->validate();
    }
}
