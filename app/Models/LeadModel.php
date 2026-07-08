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

    public function scopeAllowedForUser($query, User $user)
    {
        return match (true) {
            $user->hasRole('super_admin') => $query,
            $user->hasRole('supervisor') => $query->where('leads.id_branch', $user->id_branch),
            default => $query->where('leads.id_user', $user->id),
        };
    }

    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class, 'id_lead', 'id_lead')->latest();
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
        return $this->hasOne(SalesModel::class, 'id_lead', 'id_lead');
    }

    public function activity()
    {
        return $this->hasMany(LeadActivityModel::class, 'id_lead', 'id_lead')
            ->latest('created_at');
    }
}
