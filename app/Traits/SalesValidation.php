<?php

namespace App\Traits;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

trait SalesValidation
{
    public function validateSales(array $request, string $action = 'payment')
    {
        $rules = [];
        $messages = [];
        if ($action === 'payment') {
            $rules = [
                'paid_amount'       => 'required|numeric|min:1000',
                'payment_method'    => 'required|in:cash,transfer,debit,qris',
                'bank_name'         => 'nullable|required_if:payment_method,transfer,debit',
            ];
            $messages = [
                'paid_amount.required' => 'Nominal bayar tidak boleh kosong',
                'paid_amount.numeric' => 'Nominal bayar harus berupa angka',
                'paid_amount.min' => 'Nominal bayar minimal Rp 1.000',
                'payment_method.required' => 'Metode pembayaran belum dipilih',
                'payment_method.in' => 'Metode pembayaran harus salah satu dari: Tunai, Transfer, Debit, Qris',
                'bank_name.required_if' => 'Nama bank tidak boleh kosong',
            ];
        } elseif ($action === 'payment_cancel') {
            $rules = [
                'cancelled_reason'     => 'required|string|in:CUSTOMER_CANCELLED,PAYMENT_FAILED,ORDER_UNFULFILLABLE,DATA_ERROR,DUPLICATE,OTHER',
                'cancelled_notes'      => ['required', 'string', 'max:1000', Rule::requiredIf(fn() => $request['cancelled_reason'] === 'OTHER')],
            ];
            $messages = [
                'cancelled_reason.required' => 'Alasan pembatalan harus dipilih',
                'cancelled_reason.in' => 'Alasan pembatalan tidak sesuai dengan pilihan yang tersedia',
                'cancelled_notes.required' => 'Catatan pembatalan harus diisi jika alasan pembatalan adalah "Lainnya"',
                'cancelled_notes.max' => 'Catatan pembatalan tidak boleh lebih dari 1000 karakter',
            ];
        }
        return Validator::make(
            $request,
            $rules,
            $messages
        )->validate();
    }
}
