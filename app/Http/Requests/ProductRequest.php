<?php

namespace App\Http\Requests;

use App\Models\ProductModel;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public ?ProductModel $product = null;
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
        if ($this->isMethod('DELETE')) return []; // tidak perlu validasi apabila DELETE
        return [
            'name'                => 'required|string|max:100',
            'category'            => 'required|string|max:100',
            'item_condition'      => 'required|in:new,used,refurbished,damaged,discontinued',
            'type_item'           => 'required|in:product,sparepart,accessories,other', // 'required|in:product,sparepart,accessories,other',=>
            'link'                => 'nullable|url',
            'base_price'          => 'required|numeric|min:1000',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_price'      => 'nullable|numeric',
            'status'              => 'required|in:draft,published',
            'valid_from'          => 'nullable|date|after_or_equal:today|before_or_equal:valid_until',
            'valid_until'         => 'nullable|date|after_or_equal:valid_from',
            'image'               => 'nullable|image|max:2048|mimetypes:image/jpeg,image/png',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama produk tidak boleh kosong',
            'name.string' => 'Nama produk harus berupa teks',
            'name.max' => 'Nama produk maksimal 100 karakter',

            'category.required' => 'Kategori produk tidak boleh kosong',
            'category.string' => 'Kategori produk harus berupa teks',
            'category.max' => 'Kategori produk maksimal 100 karakter',

            'item_condition.required' => 'Kondisi produk tidak boleh kosong',
            'item_condition.in' => 'Kondisi produk tidak valid',

            'type_item.required' => 'Tipe produk harus dipilih',
            'type_item.in' => 'Tipe produk tidak valid',

            'link.url' => 'Link tidak valid',

            'base_price.required' => 'Harga produk tidak boleh kosong',
            'base_price.numeric' => 'Harga produk harus berupa angka',
            'base_price.min' => 'Harga produk minimal Rp 1.000',

            'discount_percentage.numeric' => 'Persentase diskon harus berupa angka',
            'discount_percentage.min' => 'Persentase diskon minimal 0%',
            'discount_percentage.max' => 'Persentase diskon maksimal 100%',

            'discount_price.numeric' => 'Harga diskon harus berupa angka',

            'status.required' => 'Status produk tidak boleh kosong',
            'status.in' => 'Status produk tidak valid',

            'valid_from.date' => 'Tanggal mulai tidak valid',
            'valid_from.after_or_equal' => 'Tanggal mulai harus setelah hari ini',
            'valid_from.before_or_equal' => 'Tanggal mulai harus sebelum tanggal selesai',

            'valid_until.date' => 'Tanggal selesai tidak valid',
            'valid_until.after_or_equal' => 'Tanggal selesai harus setelah tanggal mulai',
            'valid_until.before_or_equal' => 'Tanggal selesai harus sebelum tanggal mulai',

            'image.image' => 'File harus berupa gambar',
            'image.max' => 'Ukuran file maksimal 2MB',
            'image.mimetypes' => 'Format file harus JPEG atau PNG',
        ];
    }
    public function routeParameters()
    {
        return $this->route('id') ?? $this->route('product') ?? $this->route('products');
    }
    public function resolveProductModel()
    {
        if ($this->product) {
            return $this->product;
        }
        $routeParam = $this->routeParameters();
        if ($routeParam instanceof ProductModel) {
            return $this->product = $routeParam;
        }
        if ($routeParam) {
            return $this->product = ProductModel::findOrFail($routeParam);
        }
        return null;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $product = $this->resolveProductModel();

            if (!$product) return;

            if ($this->isMethod('DELETE')) {
                if (!$product->trashed()) {
                    $validator->errors()->add(
                        'error',
                        "Gagal menghapus data {$product->name} secara aman."
                    );
                }

                if ($product && $product->leadProduct()->exists() || $product->salesProduct()->exists()) {
                    $validator->errors()->add(
                        'error',
                        'Tidak dapat menghapus produk yang memiliki transaksi dan lead'
                    );
                }
            };
        });
    }
}
