<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
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
