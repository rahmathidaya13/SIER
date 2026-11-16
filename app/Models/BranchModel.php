<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class BranchModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'branches';
    protected $primaryKey = 'id_branch';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'branch_code',
        'name',
        'address',
        'is_active',
        'status',
    ];
    protected $dates    = ["deleted_at"];
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
    public static function boot()
    {
        parent::boot();
        static::creating(function ($branch) {
            $branch->branch_code    = self::generateUniqueCode();
        });
    }
    public static function generateUniqueCode()
    {
        do {
            $randomNumber = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $code = $randomNumber;
        } while (self::where('branch_code', $code)->exists());
        // Kembalikan kode yang sudah dipastikan unik
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

    public function users()
    {
        return $this->hasMany(User::class, 'id_branch', 'id_branch');
    }
    public function branchPhone()
    {
        return $this->hasMany(BranchPhoneModel::class, 'id_branch', 'id_branch');
    }
    public function customers()
    {
        return $this->hasMany(CustomerModel::class, 'created_of_branch', 'id_branch');
    }
}
