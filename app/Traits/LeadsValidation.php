<?php

namespace App\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

trait LeadsValidation
{
    protected  function isClosing(array $data): bool
    {
        return ($data['status'] ?? null) === 'CLOSED_WON'
            && ($data['temperature'] ?? null) === 'HOT';
    }
    protected function closingRules(): array
    {
        return [
            'deal_price' => [
                'required',
                'numeric',
                'min:1000',
            ],

            'paid_amount' => [
                'required',
                'numeric',
                'min:1000',
            ],

            'payment_status' => [
                'required',
                Rule::in(['LUNAS', 'DP']),
            ],

            'payment_method' => [
                'required',
                Rule::in(['cash', 'transfer', 'debit', 'qris']),
            ],
            'quantity' => ['required', 'numeric', 'min:1'],
            'bank_name'      => 'nullable|required_if:payment_method,transfer,debit',
            'notes_sales' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }
    public function validateLeads(array $request, ?string $id = null)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'phone' => [
                'required',
                'string',
                'max:15',
            ],

            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('leads', 'email')->ignore($id, 'id_lead'),
            ],

            'id_channel' => [
                'required',
                'exists:channels,id_channel',
            ],

            'id_product' => [
                'required',
                'array',
                'min:1',
            ],
            'id_product.*' => ['exists:products,id_product'],

            'temperature' => [
                'required',
                Rule::in([
                    'HOT',
                    'WARM',
                    'COLD'
                ]),
            ],
            'customer_type' => [
                'required',
                'in:NEW_CUSTOMER,REPEAT_ORDER,WIN_BACK',
            ],

            'status' => [
                'required',
                Rule::in([
                    'NEW',
                    'FOLLOW_UP',
                    'CLOSED_WON',
                    'CLOSED_LOST'
                ]),
            ],

            'follow_up_date' => [
                'nullable',
                'date'
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'new_activity' => [
                'nullable',
                'string',
                'max:2500',
            ],
        ];

        if ($this->isClosing($request)) {
            $rules = array_merge($rules, $this->closingRules());
        }
        return Validator::make(
            $request,
            $rules,
            [
                'name.required' => 'Nama prospek wajib diisi.',
                'name.max' => 'Nama maksimal 100 karakter.',

                'phone.required' => 'Nomor WhatsApp wajib diisi.',
                'phone.max' => 'Nomor WhatsApp maksimal 20 karakter.',

                'email.email' => 'Format email tidak valid.',
                'email.max' => 'Email maksimal 100 karakter.',
                'email.unique' => 'Email sudah terdaftar.',

                'id_channel.required' => 'Silakan pilih sumber prospek.',
                'id_channel.exists' => 'Sumber prospek tidak ditemukan.',

                'id_product.required' => 'Silakan pilih produk.',
                'id_product.min' => 'Silakan pilih minimal satu produk.',
                'id_product.exists' => 'Produk yang dipilih tidak valid.',

                'temperature.required' => 'Silakan pilih suhu prospek.',
                'temperature.in' => 'Suhu prospek tidak valid.',

                'customer_type.required' => 'Silakan pilih tipe pelanggan.',
                'customer_type.in' => 'Tipe pelanggan tidak valid.',

                'status.required' => 'Status prospek wajib dipilih.',
                'status.in' => 'Status prospek tidak valid.',

                'follow_up_date.date' => 'Tanggal follow up tidak valid.',

                'notes.max' => 'Catatan maksimal 1000 karakter.',

                'new_activity.max' => 'Aktivitas maksimal 5000 karakter.',

                'quantity.required' => 'Jumlah produk wajib diisi saat status prospek adalah Closing / Terjual.',
                'quantity.numeric' => 'Jumlah produk harus berupa angka.',
                'quantity.min' => 'Jumlah produk minimal 1.',

                'deal_price.required_if' => 'Harga deal wajib diisi saat status prospek adalah Closing / Terjual.',
                'deal_price.numeric' => 'Harga deal harus berupa angka.',
                'deal_price.min' => 'Harga deal minimal Rp 1.000.',

                'paid_amount.required_if' => 'Jumlah pembayaran wajib diisi saat status prospek adalah Closing / Terjual.',
                'paid_amount.numeric' => 'Jumlah pembayaran harus berupa angka.',
                'paid_amount.min' => 'Jumlah pembayaran minimal Rp 1.000.',

                'payment_status.required_if' => 'Status pembayaran wajib diisi saat status prospek adalah Closing / Terjual.',
                'payment_status.in' => 'Status pembayaran tidak valid.',

                'payment_method.required_if' => 'Metode pembayaran wajib diisi saat status prospek adalah Closing / Terjual.',
                'payment_method.in' => 'Metode pembayaran tidak valid.',

                'notes_sales.max' => 'Catatan maksimal 500 karakter.',

                'bank_name.required_if' => 'Nama bank harus diisi jika metode pembayaran Transfer atau Debit.',
                'bank_name.string' => 'Nama bank harus berupa teks.',
            ]
        )->validate();
    }
}
