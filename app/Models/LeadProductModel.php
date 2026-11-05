<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadProductModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'lead_product';
    protected $primaryKey = 'id_lead_product';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_lead',
        'id_product',
        'quantity',
        'created_at',
        'updated_at'
    ];
    protected $dates    = ["deleted_at"];


    public function lead()
    {
        return $this->belongsTo(LeadModel::class, 'id_lead', 'id_lead');
    }
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'id_product', 'id_product');
    }
}
