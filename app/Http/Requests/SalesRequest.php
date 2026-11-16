<?php

namespace App\Http\Requests;

use App\Models\SalesModel;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class SalesRequest extends FormRequest
{

    /**
     * Menyimpan instance SalesModel agar tidak perlu query ulang di Controller.
     */
    public ?SalesModel $salesRecord = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Tentukan rules berdasarkan nama method di Controller yang sedang diakses.
     */
    public function rules(): array
    {
        // Ambil nama function di Controller (contoh: 'payRemaining', 'payCancel', 'store', 'update')
        $controllerMethod = $this->route()->getActionMethod();
        return match ($controllerMethod) {
            'payRemaining'        => $this->paymentRules(),
            'payCancel' => $this->cancelRules(),
            default          => $this->paymentRules(),
        };
    }

    /**
     * Rules untuk Pembayaran (Store / Update Payment)
     */
    protected function paymentRules(): array
    {
        return [
            'paid_amount'    => ['required', 'numeric', 'min:1000'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'debit', 'qris'])],
            'bank_name'      => ['nullable', 'string', 'required_if:payment_method,transfer,debit'],
        ];
    }
    /**
     * Rules untuk Pembatalan Transaksi (Payment Cancel)
     */
    protected function cancelRules(): array
    {
        return [
            'cancelled_reason' => [
                'required',
                'string',
                Rule::in([
                    'CUSTOMER_CANCELLED',
                    'PAYMENT_FAILED',
                    'ORDER_UNFULFILLABLE',
                    'DATA_ERROR',
                    'DUPLICATE',
                    'OTHER',
                ]),
            ],
            // Diperbaiki: Gunakan 'nullable' + 'required_if' agar hanya wajib saat alasan = OTHER
            'cancelled_notes' => [
                'nullable',
                'string',
                'max:1000',
                'required_if:cancelled_reason,OTHER',
            ],
        ];
    }

    /**
     * Pesan error kustom untuk semua skenario.
     */
    public function messages(): array
    {
        return [
            // Pesan untuk Payment
            'paid_amount.required'    => 'Nominal bayar tidak boleh kosong',
            'paid_amount.numeric'     => 'Nominal bayar harus berupa angka',
            'paid_amount.min'         => 'Nominal bayar minimal Rp 1.000',
            'payment_method.required' => 'Metode pembayaran belum dipilih',
            'payment_method.in'       => 'Metode pembayaran harus salah satu dari: Tunai, Transfer, Debit, Qris',
            'bank_name.required_if'   => 'Nama bank tidak boleh kosong jika memilih Transfer atau Debit',

            // Pesan untuk Payment Cancel
            'cancelled_reason.required'   => 'Alasan pembatalan harus dipilih',
            'cancelled_reason.in'         => 'Alasan pembatalan tidak sesuai dengan pilihan yang tersedia',
            'cancelled_notes.required_if' => 'Catatan pembatalan harus diisi jika alasan pembatalan adalah "Lainnya"',
            'cancelled_notes.max'         => 'Catatan pembatalan tidak boleh lebih dari 1000 karakter',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // 1. Hanya jalankan cek tagihan jika bukan aksi cancel & jika paid_amount sudah valid angka
            $controllerMethod = $this->route()->getActionMethod();
            if ($controllerMethod === 'payCancel' || $validator->errors()->has('paid_amount')) {
                return;
            }

            // 2. Ambil data Sales berdasarkan parameter route (misal {id} atau {sales})
            $sales = $this->resolveSalesModel();
            if (!$sales) {
                return;
            }

            // 3. Bandingkan nominal bayar dengan sisa tagihan (remaining_bill)
            $paidAmount = (int) $this->input('paid_amount');
            $remaining  = (int) $sales->remaining_bill;
            $status     = $sales->status;
            if ($paidAmount !== $remaining) {
                $operator = $paidAmount > $remaining
                    ? 'melebihi dari'
                    : 'kurang dari';

                $validator->errors()->add(
                    'paid_amount',
                    'Nominal pembayaran tidak boleh '
                        . $operator
                        . ' jumlah tagihan sebesar Rp '
                        . number_format($remaining, 0, ',', '.')
                );
            }

            // 4. Bandingkan nominal bayar dengan total tagihan (deal_price)
            if ($paidAmount > $sales->deal_price) {
                $validator->errors()->add(
                    'paid_amount',
                    'Nominal pembayaran tidak boleh melebihi dari jumlah tagihan sebesar Rp '
                        . number_format($sales->deal_price, 0, ',', '.')
                );
            }

            if ($status === 'CANCELLED') {
                $validator->errors()->add(
                    'error',
                    'Transaksi ini sudah dibatalkan sebelumnya.',
                );
            }
        });
    }
    public function routeParameter(): string
    {
        return $this->route('id') ??
            $this->route('sales') ??
            $this->route('sale') ??
            $this->route('id_sales');
    }
    public function resolveSalesModel(): ?SalesModel
    {
        if ($this->salesRecord) {
            return $this->salesRecord;
        }
        // Cek parameter route (bisa bernama 'id', 'sale', atau 'sales' tergantung routes/web.php Anda)
        $routeParam = $this->routeParameter();
        if ($routeParam instanceof SalesModel) {
            return $this->salesRecord = $routeParam;
        }
        if ($routeParam) {
            // Sekalian eager load creator (withTrashed) untuk kebutuhan SalesPolicy di Controller
            return $this->salesRecord = SalesModel::findOrFail($routeParam);
        }
        return null;
    }
}
