<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BranchPhoneModel extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'branch_phone';
    protected $primaryKey = 'branch_phone_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id_branch',
        'phone',
    ];
    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'id_branch', 'id_branch');
    }
}
