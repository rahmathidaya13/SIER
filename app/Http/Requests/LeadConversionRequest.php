<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadConversionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'deal_price'     => 'required|numeric|min:1000',
            'paid_amount'    => 'required|numeric|min:1000',
            'payment_status' => 'required|in:LUNAS,DP',
            'bank_name'      => 'nullable|required_if:payment_method,transfer,debit',
            'payment_method' => 'required|in:cash,transfer,debit,qris',
            'quantity'       => 'required|numeric|min:1',
            'notes'          => 'nullable|string|max:500',
        ];
    }
    public function messages()
    {
        return [
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
            'notes.max' => 'Catatan maksimal 500 karakter',

            'quantity.required' => 'Jumlah barang tidak boleh kosong',
            'quantity.numeric' => 'Jumlah barang harus berupa angka',
            'quantity.min' => 'Jumlah barang minimal 1',

            'bank_name.required_if' => 'Nama bank harus diisi jika metode pembayaran Transfer atau Debit',
            'bank_name.string' => 'Nama bank harus berupa teks',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $lead = $this->route('leads');
            if ($lead) {
                if ($lead->status === 'CLOSED_WON' || $lead->sales()->exists()) {
                    // Pakai key 'error' agar errornya bisa ditangkap secara global di frontend
                    $validator->errors()->add(
                        'error',
                        'Lead ini sudah pernah dikonversi menjadi Sales.'
                    );
                    return;
                }
            }

            $status = $this->input('payment_status');

            // Kita hanya peduli jika statusnya adalah 'DP'
            if ($status === 'DP') {
                $dealPrice  = $this->input('deal_price', 0);
                $paidAmount = $this->input('paid_amount', 0);

                $minimalDp = $dealPrice * 0.5; // Minimal 50%

                // Jika DP kurang dari 50%
                if ($paidAmount < $minimalDp) {
                    $formattedMin = number_format($minimalDp, 0, ',', '.');

                    // Tambahkan pesan error langsung ke field 'paid_amount'
                    $validator->errors()->add(
                        'paid_amount',
                        "Uang muka (DP) minimal harus 50% (Minimal: Rp {$formattedMin})"
                    );
                }
            }
        });
    }
}
