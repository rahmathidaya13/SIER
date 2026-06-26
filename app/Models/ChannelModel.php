<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelModel extends Model
{
    use HasFactory, HasUuids;
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
    public function leads()
    {
        return $this->hasMany(LeadModel::class);
    }
}
