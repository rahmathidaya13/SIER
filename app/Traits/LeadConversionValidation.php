<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait LeadConversionValidation
{
    public function validateLeadConversion(array $request)
    {
        return Validator::make(
            $request,
            [
                'deal_price'     => 'required|numeric|min:1000',
                'paid_amount'    => 'required|numeric|min:1000',
                'payment_status' => 'required|in:LUNAS,DP',
                'payment_method' => 'required|in:cash,transfer,debit,qris',
                'notes'          => 'nullable|string|max:500',
            ],
            [
                'deal_price.required' => 'Harga deal tidak boleh kosong',
                'deal_price.numeric' => 'Harga deal harus berupa angka',
                'deal_price.min' => 'Harga deal minimal Rp 1.000',
                'paid_amount.required' => 'Jumlah pembayaran tidak boleh kosong',
                'paid_amount.numeric' => 'Jumlah pembayaran harus berupa angka',
                'paid_amount.min' => 'Jumlah pembayaran minimal Rp 1.000',
                'payment_status.required' => 'Status pembayaran tidak boleh kosong',
                'payment_status.in' => 'Status pembayaran harus LUNAS atau DP',
                'payment_method.required' => 'Metode pembayaran tidak boleh kosong',
                'payment_method.in' => 'Metode pembayaran harus salah satu dari: Tunai, Transfer, Debit, Qris',
                'notes.string' => 'Catatan harus berupa teks',
                'notes.max' => 'Catatan maksimal 500 karakter'
            ]
        )->validate();
    }
}
