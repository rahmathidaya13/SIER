<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\ProductModel;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function productSearch(Request $request)
    {
        $keyword = $request->input('keyword');
        $products = ProductModel::with('prices')
            ->select('id_product', 'name', 'image_link', 'image_path')
            ->when($keyword, function ($q, $search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('id_product', 'like', "%{$search}%");
            })
            ->limit(10) // HANYA AMBIL 10 TERATAS!
            ->get();

        return response()->json($products);
    }
}
