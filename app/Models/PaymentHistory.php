<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentHistory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'payment_histories';
    protected $primaryKey = 'id_payment_history';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'id_sales',
        'id_user',
        'id_lead',
        'payment_method',
        'bank_name',
        'paid_amount',
        'payment_at',
        'notes_payment',
    ];
    protected $dates    = ["deleted_at"];

    protected $casts = [
        'payment_at' => 'datetime',
        'paid_amount' => 'integer',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
    public function lead()
    {
        return $this->belongsTo(LeadModel::class, 'id_lead', 'id_lead');
    }
    public function sale()
    {
        return $this->belongsTo(SalesModel::class, 'id_sales', 'id_sales');
    }
}
