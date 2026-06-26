<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesModel extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'sales';
    protected $primaryKey = 'id_sales';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'invoice_code',
        'id_lead',
        'id_user',
        'id_product',
        'id_branch',
        'deal_price',
        'payment_status',
        'paid_amount',
        'payment_method',
        'deal_at',
        'closing_note',
    ];
    protected $dates    = ["deleted_at"];
    public function scopeAllowedForUser($query)
    {
        $user = auth()->user();

        // Jika yang login Orang Pusat (Superadmin), BUKA SEMUA DATA!
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Jika yang login SPV/Sales/Admin Cabang, KUNCI HANYA DI CABANG DIA!
        return $query->where('id_branch', $user->id_branch);
    }
    public function lead()
    {
        return $this->belongsTo(LeadModel::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'id_product', 'id_product');
    }
    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'id_branch', 'id_branch');
    }
}
