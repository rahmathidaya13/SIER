<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPriceModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'product_price';
    protected $primaryKey = 'id_product_price';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_product',
        'created_by',
        'status',
        'valid_from',
        'valid_until',
        'base_price',
        'discount_percentage',
        'discount_price',
        'price_type',
    ];
    protected $dates    = ["deleted_at"];
    protected $casts = [
        'base_price' => 'integer',
        'discount_percentage' => 'integer',
        'discount_price' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'id_product', 'id_product');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
