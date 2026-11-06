<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class LeadModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'leads';
    protected $primaryKey = 'id_lead';
    public $incrementing = false; // Karena pakai UUID
    public $timestamps = true;
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'updated_by',
        'id_channel',
        'id_branch',
        'id_customer',
        'temperature',
        'status',
        'follow_up_date',
        'notes',
        'closed_at',

        'archived_at',
        'archived_by',
        'created_at',
        'updated_at'

    ];
    protected $dates    = ["deleted_at"];
    protected $casts = [
        'follow_up_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
    ];
    public function scopeJoinUserRole(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query // 1. Join User & Role untuk Creator (created_by)
            ->leftJoin('users as creator', 'creator.id', '=', "{$table}.created_by")
            ->leftJoin('model_has_roles as creator_mhr', function ($join) {
                $join->on('creator_mhr.model_id', '=', 'creator.id')
                    ->where('creator_mhr.model_type', User::class);
            })
            ->leftJoin('roles as creator_role', 'creator_role.id', '=', 'creator_mhr.role_id')

            // 2. Join User & Role untuk Updater (updated_by)
            ->leftJoin('users as updater', 'updater.id', '=', "{$table}.updated_by")
            ->leftJoin('model_has_roles as updater_mhr', function ($join) {
                $join->on('updater_mhr.model_id', '=', 'updater.id')
                    ->where('updater_mhr.model_type', User::class);
            })
            ->leftJoin('roles as updater_role', 'updater_role.id', '=', 'updater_mhr.role_id');
    }

    public function scopeAllowedForUser($query)
    {
        $user = auth()->user();
        if (!$user) return $query->whereRaw('1 = 0'); // Jika user tidak ada, kembalikan query kosong
        return match (true) {
            $user->hasRole('developer') => $query,
            $user->hasRole('super_admin') => $query,
            $user->hasRole('supervisor') => $query->where('leads.id_branch', $user->id_branch),
            default => $query->where('leads.created_by', $user->id),
        };
    }
    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class, 'id_lead', 'id_lead')->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->withTrashed();
    }

    public function channel()
    {
        return $this->belongsTo(ChannelModel::class, 'id_channel', 'id_channel');
    }

    public function leadProduct()
    {
        return $this->hasMany(LeadProductModel::class, 'id_lead', 'id_lead');
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
    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'id_customer', 'id_customer');
    }
}
