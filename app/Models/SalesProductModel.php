<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesProductModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'sales_product';
    protected $primaryKey = 'id_sales_product';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_sales',
        'id_product',
        'created_at',
        'updated_at'
    ];

    protected $dates = ['deleted_at'];

    public function sales()
    {
        return $this->belongsTo(SalesModel::class, 'id_sales', 'id_sales');
    }
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'id_product', 'id_product');
    }
}
