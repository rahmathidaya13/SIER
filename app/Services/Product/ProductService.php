<?php

namespace App\Services\Product;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\ProductModel;
use App\Models\ProductPriceModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function store($validated)
    {
        return DB::transaction(function () use ($validated) {

            $imagePath = null;
            if (request()->hasFile('image')) {
                $imagePath = request()->file('image')->store('product_image', 'public');
            }

            $product = ProductModel::create([
                'created_by' => auth()->id(),
                'source' => 'manual',
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'category' => $validated['category'],
                'type_item' => $validated['type_item'],
                'item_condition' => $validated['item_condition'],
                'image_path' => $imagePath,
                'link' => $validated['link'],
            ]);

            ProductPriceModel::create([
                'id_product'          => $product->id_product,
                'created_by'          => auth()->id(),
                'status'              => $validated['status'],
                'valid_from'          => $validated['valid_from'] ?? Carbon::now()->toDateString(),
                'valid_until'         => $validated['valid_until'] ?? null,
                'base_price'          => $validated['base_price'],
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'discount_price'      => $validated['discount_price'] ?? 0,
                'price_type'          => ($validated['discount_price'] > 0) ? 'discount' : 'normal',
            ]);
            return $product;
        });
    }

    public function update(ProductModel $product, $validated)
    {
        return DB::transaction(function () use ($product, $validated) {
            $imageCover = null;
            if (request()->hasFile('image')) {
                if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $imageCover = request()->file('image')
                    ->store('product_image', 'public');
            } else {
                $imageCover = $product->image_path;
            }

            $product->update([
                'name'           => $validated['name'],
                'created_by'     => auth()->id(),
                'source'         => request()->hasFile('image') ? 'manual' : 'scrape',
                'slug'           => Str::slug($validated['name']) . '-' . Str::random(5),
                'category'       => $validated['category'],
                'type_item'      => $validated['type_item'],
                'item_condition' => $validated['item_condition'],
                'link'           => $validated['link'] ?? $product->link,
                'image_path'     => $imageCover ?? $product->image_path,
            ]);

            $product->prices()->updateOrCreate(['id_product' => $product->id_product], [
                'id_product'          => $product->id_product,
                'created_by'          => auth()->id(),
                'status'              => $validated['status'],
                'valid_from'          => $validated['valid_from'] ?? Carbon::now()->toDateString(),
                'valid_until'         => $validated['valid_until'] ?? null,
                'base_price'          => $validated['base_price'],
                'discount_percentage' => $validated['discount_percentage'] ?? 0,
                'discount_price'      => $validated['discount_price'] ?? 0,
                'price_type'          => ($validated['discount_price'] > 0) ? 'discount' : 'normal',
            ]);

            return $product;
        });
    }

    public function delete(ProductModel $product)
    {
        return DB::transaction(function () use ($product) {
            if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $product->prices()->delete();
            $product->delete();
            return $product;
        });
    }
}
