<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelModel extends Model
{
    use HasFactory, HasUuids,SoftDeletes;
    protected $table = 'channels';
    protected $primaryKey = 'id_channel';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'name',
        'code',
        'icon',
    ];
    protected $dates    = ["deleted_at"];


    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function leads()
    {
        return $this->hasMany(LeadModel::class, 'id_channel', 'id_channel');
    }
    public function scopeAllowedForUser($query)
    {
        $user = auth()->user();

        // 1. Super Admin: Bebas lihat semua channel di seluruh cabang
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // 2. Supervisor & Sales: Bisa lihat SEMUA channel, asalkan dibuat oleh user
        // yang memiliki id_branch yang sama dengan mereka (Satu Cabang)
        return $query->whereHas('leads', function ($q) use ($user) {
            $q->where('id_branch', $user->id_branch);
        });
    }
}
