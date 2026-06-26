<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'leads';
    protected $primaryKey = 'id_lead';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_user',
        'id_channel',
        'id_product',
        'id_branch',
        'name',
        'phone',
        'email',
        'temperature',
        'status',
        'follow_up_date',
        'notes',
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
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function channel()
    {
        return $this->belongsTo(ChannelModel::class, 'id_channel', 'id_channel');
    }

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'id_product', 'id_product');
    }

    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'id_branch', 'id_branch');
    }
    public function sales()
    {
        return $this->hasOne(SalesModel::class);
    }
}
