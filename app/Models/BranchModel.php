<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function branchPhone()
    {
        return $this->hasMany(BranchPhoneModel::class, 'id_branch', 'id_branch');
    }
}
