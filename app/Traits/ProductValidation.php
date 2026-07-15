<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;

trait ProductValidation
{
    public function validateProduct(array $request)
    {
        return Validator::make(
            $request,
            [
                'name'                => 'required|string|max:100',
                'category'            => 'required|string|max:100',
                'item_condition'      => 'required|in:new,used,refurbished,damaged,discontinued',
                'type_item'           => 'required|in:product,sparepart,accessories,other', // 'required|in:product,sparepart,accessories,other',=>
                'link'                => 'nullable|url',
                'base_price'          => 'required|numeric|min:1000',
                'discount_percentage' => 'nullable|numeric|min:0|max:100',
                'discount_price'      => 'nullable|numeric',
                'status'              => 'required|in:draft,published',
                'valid_from'          => 'nullable|date',
                'valid_until'         => 'nullable|date|after_or_equal:valid_from',
                'image'               => 'nullable|image|max:2048|mimetypes:image/jpeg,image/png',
            ],
            [
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

                'valid_until.date' => 'Tanggal selesai tidak valid',
                'valid_until.after_or_equal' => 'Tanggal selesai harus setelah tanggal mulai',

                'image.image' => 'File harus berupa gambar',
                'image.max' => 'Ukuran file maksimal 2MB',
                'image.mimetypes' => 'Format file harus JPEG atau PNG',
            ]
        )->validate();
    }
}
