<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileModel extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'profiles';
    protected $primaryKey = 'id_profile';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_user',
        'gender',
        'phone',
        'address',
        'photo',
        'city',
        'province',
    ];
    protected $dates    = ["deleted_at"];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
