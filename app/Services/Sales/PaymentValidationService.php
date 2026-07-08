<?php

namespace App\Services\Sales;

use App\Models\SalesModel;
use Illuminate\Validation\ValidationException;

class PaymentValidationService
{
    public function validateFullPayment(SalesModel $sales, int|float $paidAmount)
    {
        $remaining = (int) $sales->remaining_bill;

        if ($paidAmount === $remaining) {
            return;
        }

        $operator = $paidAmount > $remaining
            ? 'melebihi dari'
            : 'kurang dari';

        throw ValidationException::withMessages([
            'paid_amount' =>
            'Nominal pembayaran tidak boleh '
                . $operator
                . ' jumlah tagihan sebesar Rp '
                . number_format($remaining, 0, ',', '.'),
        ]);
    }
}
