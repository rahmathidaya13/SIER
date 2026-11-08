<?php

namespace App\Http\Requests;

use App\Models\LeadModel;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class LeadRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat request ini.
     * Logika policy/gate bisa ditaruh di sini atau tetap di Controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Mengecek apakah lead sedang di-closing (Deal).
     */
    protected function isClosing(): bool
    {
        return $this->input('status') === 'CLOSED_WON'
            && $this->input('temperature') === 'HOT';
    }

    /**
     * Tentukan aturan validasi (rules).
     */
    public function rules(): array
    {
        // CEK APAKAH INI PROSES UPDATE ATAU STORE
        // Jika di route ada parameter 'id' atau 'lead', berarti ini UPDATE.
        $leadId = $this->route('id') ?? $this->route('lead');

        // Ambil data lama dari database HANYA JIKA ini proses Update
        $lead = $leadId ? LeadModel::find($leadId) : null;
        // 1. Aturan Dasar (Base Rules)
        $rules = [
            'id_customer' => ['required', 'exists:customers,id_customer'],
            'id_channel'  => ['required', 'exists:channels,id_channel'],
            'id_product'  => ['required', 'array', 'min:1'],
            'id_product.*' => ['exists:products,id_product'],

            'temperature' => [
                'required',
                Rule::in(['HOT', 'WARM', 'COLD'],),
                function ($attribute, $value, $fail) use ($lead) {
                    if ($lead && $lead->temperature === 'HOT' && $value !== 'HOT') {
                        $fail('Tidak dapat mengubah suhu prospek yang sudah HOT menjadi suhu lain.');
                    }
                },
            ],
            'status'      => [
                'required',
                Rule::in(['NEW', 'FOLLOW_UP', 'CLOSED_WON', 'CLOSED_LOST']),
                function ($attribute, $value, $fail) use ($lead) {
                    if ($lead && $lead->status === 'CLOSED_WON' && $value !== 'CLOSED_WON') {
                        $fail('Tidak dapat mengubah status prospek yang sudah disahkan menjadi status lain.');
                    }
                },
            ],

            'follow_up_date' => ['nullable', 'date'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'new_activity'   => ['nullable', 'string', 'max:2500'],
        ];

        // 2. Gabungkan aturan penutupan (Closing Rules) jika memenuhi syarat
        if ($this->isClosing()) {
            $rules = array_merge($rules, [
                'deal_price'     => ['required', 'numeric', 'min:1000'],
                'paid_amount'    => ['required', 'numeric', 'min:1000'],
                'payment_status' => ['required', Rule::in(['LUNAS', 'DP'])],
                'payment_method' => ['required', Rule::in(['cash', 'transfer', 'debit', 'qris'])],
                'quantity'       => ['required', 'numeric', 'min:1'],

                // Gunakan required_if dari Form Request
                'bank_name'      => ['nullable', 'required_if:payment_method,transfer,debit', 'string'],
                'notes_sales'    => ['nullable', 'string', 'max:500'],
            ]);
        }

        return $rules;
    }

    /**
     * Kustomisasi pesan error.
     */
    public function messages(): array
    {
        return [
            'id_customer.required' => 'Silakan pilih pelanggan.',
            'id_customer.exists'   => 'Pelanggan tidak ditemukan.',
            'id_channel.required'  => 'Silakan pilih sumber prospek.',
            'id_channel.exists'    => 'Sumber prospek tidak ditemukan.',

            'id_product.required'  => 'Silakan pilih produk.',
            'id_product.min'       => 'Silakan pilih minimal satu produk.',
            'id_product.*.exists'  => 'Produk yang dipilih tidak valid.',

            'temperature.required' => 'Silakan pilih suhu prospek.',
            'temperature.in'       => 'Suhu prospek tidak valid.',
            'status.required'      => 'Status prospek wajib dipilih.',
            'status.in'            => 'Status prospek tidak valid.',

            'follow_up_date.date'  => 'Tanggal follow up tidak valid.',
            'notes.max'            => 'Catatan maksimal 1000 karakter.',
            'new_activity.max'     => 'Aktivitas maksimal 2500 karakter.', // Disamakan dengan max:2500 di rules

            // Pesan Closing
            'quantity.required'      => 'Jumlah produk wajib diisi saat status prospek adalah Closing / Terjual.',
            'quantity.numeric'       => 'Jumlah produk harus berupa angka.',
            'quantity.min'           => 'Jumlah produk minimal 1.',

            'deal_price.required'    => 'Harga deal wajib diisi saat status prospek adalah Closing / Terjual.',
            'deal_price.numeric'     => 'Harga deal harus berupa angka.',
            'deal_price.min'         => 'Harga deal minimal Rp 1.000.',

            'paid_amount.required'   => 'Jumlah pembayaran wajib diisi saat status prospek adalah Closing / Terjual.',
            'paid_amount.numeric'    => 'Jumlah pembayaran harus berupa angka.',
            'paid_amount.min'        => 'Jumlah pembayaran minimal Rp 1.000.',

            'payment_status.required' => 'Status pembayaran wajib diisi saat status prospek adalah Closing / Terjual.',
            'payment_status.in'      => 'Status pembayaran tidak valid.',

            'payment_method.required' => 'Metode pembayaran wajib diisi saat status prospek adalah Closing / Terjual.',
            'payment_method.in'      => 'Metode pembayaran tidak valid.',

            'notes_sales.max'        => 'Catatan maksimal 500 karakter.',
            'bank_name.required_if'  => 'Nama bank harus diisi jika metode pembayaran Transfer atau Debit.',
            'bank_name.string'       => 'Nama bank harus berupa teks.',
        ];
    }

    /**
     * Logika keamanan tambahan setelah rule dasar selesai divalidasi.
     * Di sini kita pasang logika "Uang muka minimal 50%" yang sebelumnya ada di Controller.
     */

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Hanya jalankan jika sedang proses closing dan berstatus DP
            if ($this->isClosing() && $this->input('payment_status') === 'DP') {
                $dealPrice  = $this->input('deal_price', 0);
                $paidAmount = $this->input('paid_amount', 0);
                $minimalDp  = $dealPrice * 0.5;

                if ($paidAmount < $minimalDp) {
                    $formattedMin = number_format($minimalDp, 0, ',', '.');
                    $validator->errors()->add(
                        'paid_amount',
                        "Uang muka (DP) minimal harus 50% (Minimal: Rp {$formattedMin})"
                    );
                }
            }
        });
    }
}
