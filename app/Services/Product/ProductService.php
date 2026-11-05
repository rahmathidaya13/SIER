<?php

namespace App\Services\Product;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\ProductModel;
use App\Models\ProductPriceModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProductService
{
    public function store(array $validated)
    {
        $newImagePath = null;
        try {
            return DB::transaction(function () use ($validated, &$newImagePath) {

                if (request()->hasFile('image')) {
                    $newImagePath = $this->storeOptimizedImage(request()->file('image'));
                }

                $product = ProductModel::create([
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                    'source' => 'manual',
                    'name' => $validated['name'],
                    'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                    'category' => $validated['category'],
                    'type_item' => $validated['type_item'],
                    'item_condition' => $validated['item_condition'],
                    'image_path' => $newImagePath,
                    'link' => $validated['link'],
                ]);

                // Buat harga produk baru
                ProductPriceModel::create([
                    'id_product'          => $product->id_product,
                    'created_by'          => auth()->id(),
                    'status'              => $validated['status'],
                    'valid_from'          => $validated['valid_from'] ?? null,
                    'valid_until'         => $validated['valid_until'] ?? null,
                    'base_price'          => $validated['base_price'],
                    'discount_percentage' => $validated['discount_percentage'] ?? 0,
                    'discount_price'      => $validated['discount_price'] ?? 0,
                    'price_type'          => ($validated['discount_price'] > 0) ? 'discount' : 'normal',
                ]);
                return $product;
            });
        } catch (\Throwable $error) {
            // Bersihkan file baru jika transaksi database gagal.
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            Log::error('Gagal membuat produk.', [
                'message' => $error->getMessage(),
            ]);

            throw $error;
        }
    }

    public function update(ProductModel $product, $validated)
    {
        $newImagePath = null;
        $oldImagePath = $product->image_path;
        try {

            $updatedProduct  = DB::transaction(function () use ($product, $validated, &$newImagePath) {
                $hasNewImage = request()->hasFile('image');

                if ($hasNewImage) {
                    $newImagePath = $this->storeOptimizedImage(request()->file('image'));
                }

                $product->update([
                    'name'           => $validated['name'],
                    'updated_by'     => $product->created_by !== auth()->id() ? auth()->id() : $product->created_by,
                    'source'         => $hasNewImage ? 'manual' : $product->source,
                    'slug'           => Str::slug($validated['name']) . '-' . Str::random(5),
                    'category'       => $validated['category'],
                    'type_item'      => $validated['type_item'],
                    'item_condition' => $validated['item_condition'],
                    'link'           => $validated['link'] ?? $product->link,
                    'image_path'     => $hasNewImage ? $newImagePath : $product->image_path,
                ]);

                // Update atau buat harga produk baru
                $product->prices()->updateOrCreate(['id_product' => $product->id_product], [
                    'id_product'          => $product->id_product,
                    'created_by'          => $product->created_by !== auth()->id() ? auth()->id() : $product->created_by,
                    'status'              => $validated['status'],
                    'valid_from'          => $validated['valid_from'] ?? null,
                    'valid_until'         => $validated['valid_until'] ?? null,
                    'base_price'          => $validated['base_price'],
                    'discount_percentage' => $validated['discount_percentage'] ?? 0,
                    'discount_price'      => $validated['discount_price'] ?? 0,
                    'price_type'          => ($validated['discount_price'] > 0) ? 'discount' : 'normal',
                ]);

                return $product;
            });
            // Hapus gambar lama setelah transaksi berhasil.
            if (
                $newImagePath &&
                $oldImagePath &&
                $oldImagePath !== $newImagePath
            ) {
                Storage::disk('public')->delete($oldImagePath);
            }

            return $updatedProduct;
        } catch (\Throwable $error) {
            // Hapus file baru jika transaksi gagal.
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }

            Log::error('Gagal memperbarui produk.', [
                'product_id' => $product->id_product,
                'message' => $error->getMessage(),
            ]);

            throw $error;
        }
    }

    public function delete(ProductModel $product)
    {
        $imagePath = $product->image_path;

        $deletedProduct = DB::transaction(function () use ($product) {
            $product->prices()->delete();
            $product->delete();

            return $product;
        });

        // File dihapus setelah transaksi database berhasil.
        if ($imagePath) {
            Storage::disk('public')->delete($imagePath);
        }

        return $deletedProduct;
    }

    private function storeOptimizedImage(UploadedFile $file)
    {
        $filename = Str::ulid() . '.webp';
        $path = 'product_image/' . $filename;

        $encodedImage = Image::read($file)
            ->scaleDown(600,  600)
            ->toWebp(78);

        $stored = Storage::disk('public')->put(
            $path,
            (string) $encodedImage
        );

        if (!$stored) {
            throw new \RuntimeException(
                'Gagal menyimpan gambar produk.'
            );
        }

        return $path;
    }
}
