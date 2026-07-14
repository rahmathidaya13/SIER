<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'products';
    protected $primaryKey = 'id_product';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'product_code',
        'source',
        'name',
        'slug',
        'category',
        'item_condition',
        'link',
        'image_link',
        'image_path',
    ];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->product_code)) {
                $product->product_code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode()
    {
        do {
            $randomNumber = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $code = $randomNumber;
        } while (self::where('product_code', $code)->exists());
        // Kembalikan kode yang sudah dipastikan unik
        return $code;
    }

    public function leads()
    {
        return $this->hasMany(LeadModel::class, 'id_product', 'id_product');
    }
    public function sales()
    {
        return $this->hasMany(SalesModel::class);
    }
    public function prices()
    {
        return $this->hasMany(ProductPriceModel::class, 'id_product', 'id_product');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
