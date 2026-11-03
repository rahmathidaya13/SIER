<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_branch',
        'name',
        'email',
        'password',
        'is_active',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];
    protected $dates    = ["deleted_at"];

    public function sendPasswordResetNotification($token)
    {
        $this->notify(
            new ResetPasswordNotification($token)
        );
    }
    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'id_branch', 'id_branch');
    }

    public function sales()
    {
        return $this->hasMany(SalesModel::class, 'id_user', 'id');
    }

    public function leads()
    {
        return $this->hasMany(LeadModel::class, 'id_user', 'id');
    }

    public function products()
    {
        return $this->hasMany(ProductModel::class, 'id_user', 'id');
    }

    public function productPrice()
    {
        return $this->hasMany(ProductPriceModel::class, 'id_user', 'id');
    }

    public function profile()
    {
        return $this->hasOne(ProfileModel::class, 'id_user', 'id');
    }
    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class, 'id_user', 'id');
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class, 'id_user', 'id');
    }

    public function customers()
    {
        return $this->hasMany(CustomerModel::class, 'created_by', 'id');
    }
    public function customer_updaters()
    {
        return $this->hasMany(CustomerModel::class, 'updated_by', 'id');
    }
}
