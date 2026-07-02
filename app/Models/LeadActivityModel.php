<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class LeadActivityModel extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'lead_activity';
    protected $primaryKey = 'id_lead_activity';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_lead',
        'id_user',
        'updated_by',
        'description',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
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
