<?php

namespace App\Models;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeadActivityModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'lead_activity';
    protected $primaryKey = 'id_lead_activity';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_lead',
        'created_by',
        'updated_by',
        'source',
        'updated_by',
        'description',
    ];
    protected $dates = ['deleted_at'];
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function userUpdate()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    public function lead()
    {
        return $this->belongsTo(LeadModel::class, 'id_lead', 'id_lead');
    }
}
