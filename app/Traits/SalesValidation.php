<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait SalesValidation
{
    public function validateSales(array $request)
    {
        return Validator::make(
            $request,
            [
                'paid_amount'       => 'required|numeric|min:1000',
                'payment_method'    => 'required|in:cash,transfer,debit,qris',
                'bank_name'         => 'nullable|required_if:payment_method,transfer,debit',
            ],
            [
                'paid_amount.required' => 'Nominal bayar tidak boleh kosong',
                'paid_amount.numeric' => 'Nominal bayar harus berupa angka',
                'paid_amount.min' => 'Nominal bayar minimal Rp 1.000',
                'payment_method.required' => 'Metode pembayaran tidak boleh kosong',
                'payment_method.in' => 'Metode pembayaran harus salah satu dari: Tunai, Transfer, Debit, Qris',
                'bank_name.required_if' => 'Nama bank tidak boleh kosong'
            ]
        )->validate();
    }
}
