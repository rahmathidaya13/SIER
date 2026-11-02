<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class CustomerModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'customers';
    protected $primaryKey = 'id_customer';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'updated_by',
        'created_of_branch',
        'customer_code',
        'name',
        'pic_name',
        'phone',
        'email',
        'tax_number',
        'business_type',
        'customer_type',
        'customer_tag',
        'status',
        'is_member',
        'city',
        'address',
    ];
    protected $dates    = ["deleted_at"];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_member' => 'boolean',
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

    /**
     * Generate kode unik pelanggan (Contoh: CUST-2610-X9B2).
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->customer_code = self::generateUniqueCode();
        });
    }
    public static function generateUniqueCode()
    {
        $prefix = 'CUST-' . now()->format('ym') . '-';

        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (CustomerModel::withTrashed()->where('customer_code', $code)->exists());

        return $code;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->withTrashed();
    }
    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'created_of_branch', 'id_branch');
    }
    public function leads()
    {
        return $this->hasMany(LeadModel::class, 'id_customer', 'id_customer');
    }

    public function sales()
    {
        return $this->hasMany(SalesModel::class, 'id_customer', 'id_customer');
    }
}
