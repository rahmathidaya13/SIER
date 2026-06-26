<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'products';
    protected $primaryKey = 'id_product';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'source',
        'name',
        'slug',
        'category',
        'item_condition',
        'link',
        'image_link',
        'image_path',
    ];
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
