<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

trait UserValidation
{
    public function validationText(
        array $request,
        array $allowedRoles,
        User $actor,
        ?User $targetUser = null,
        string $mode = 'create'
    ) {
        return Validator::make(
            $request,
            [
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
                    Rule::unique('users', 'email')
                        ->ignore($targetUser?->id),
                ],
                'password' => $mode === 'edit'
                    ? ['nullable', 'string', 'min:8']
                    : ['required', 'string', 'min:8'],

                'role' => [
                    'required',
                    Rule::in($allowedRoles),
                ],

                'id_branch' => $actor->hasRole('super_admin')
                    ? ['required', 'exists:branches,id_branch']
                    : ['nullable'],
                'is_active' => ['required', 'in:active,inactive'],
            ],
            [
                'name.required'      => 'Nama tidak boleh kosong.',
                'name.max'           => 'Nama maksimal 100 karakter.',

                'email.required'     => 'Email tidak boleh kosong.',
                'email.email'        => 'Email tidak valid.',
                'email.unique'       => 'Email sudah terdaftar.',

                'password.required'  => 'Password tidak boleh kosong.',
                'password.min'       => 'Password minimal 8 karakter.',

                'role.required'      => 'Role tidak boleh kosong.',
                'role.in'            => 'Role tidak valid.',

                'id_branch.required' => 'Cabang tidak boleh kosong.',
                'id_branch.exists'   => 'Cabang tidak valid.',

                'is_active.required' => 'Status wajib dipilih.',
                'is_active.in'  => 'Status tidak valid.',
            ]
        )->validate();
    }
}
