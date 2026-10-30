<?php

namespace App\Models;

use App\Traits\HasRoleJoin;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SalesModel extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'sales';
    protected $primaryKey = 'id_sales';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'invoice',
        'id_lead',
        'created_by',
        'updated_by',
        'id_branch',
        'id_customer',
        'deal_price',
        'remaining_bill',
        'paid_amount',
        'status',
        'payment_status',
        'quantity',
        'deal_at',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancelled_reason',
        'cancelled_notes',
    ];
    protected $dates    = ["deleted_at"];
    protected $casts = [
        'deal_price' => 'integer',
        'remaining_bill' => 'integer',
        'paid_amount' => 'integer',
        'deal_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function scopeJoinUserRole(Builder $query): Builder
    {
        $table = $this->getTable();

        return $query // 1. Join User & Role untuk Creator (created_by)
            ->leftJoin('users as creator', 'creator.id', '=', "{$table}.created_by")
            ->leftJoin('model_has_roles as creator_mhr', function ($join) {
                $join->on('creator_mhr.model_id', '=', 'creator.id')
                    ->where('creator_mhr.model_type', User::class);
            })
            ->leftJoin('roles as creator_role', 'creator_role.id', '=', 'creator_mhr.role_id')

            // 2. Join User & Role untuk Updater (updated_by)
            ->leftJoin('users as updater', 'updater.id', '=', "{$table}.updated_by")
            ->leftJoin('model_has_roles as updater_mhr', function ($join) {
                $join->on('updater_mhr.model_id', '=', 'updater.id')
                    ->where('updater_mhr.model_type', User::class);
            })
            ->leftJoin('roles as updater_role', 'updater_role.id', '=', 'updater_mhr.role_id');
    }
    public function scopeAllowedForUser($query)
    {
        $user = auth()->user();
        if (!$user) return $query->whereRaw('1 = 0'); // Jika user tidak ada, kembalikan query kosong
        return match (true) {
            $user->hasRole('developer') => $query,
            $user->hasRole('super_admin') => $query,
            $user->hasRole('supervisor') => $query->where('sales.id_branch', $user->id_branch),
            default => $query->where('sales.created_by', $user->id),
        };
    }
    public static function boot()
    {
        parent::boot();
        static::creating(function ($sale) {
            // hanya generate jika kosong (biar bisa manual input juga)
            if (empty($sale->invoice)) {
                $sale->invoice = self::generateInvoice();
            }
        });
    }
    public static function generateInvoice()
    {
        // 1. Buat Gembok (Lock) Antrean bernama 'generate_invoice_lock'
        // Berlaku selama maksimal 10 detik untuk mencegah nyangkut
        $now = now();
        $year = $now->format('Y');
        $month = $now->format('m');

        // Sesuai format Anda: IN/2026/03/
        $prefix = "INV/{$year}/{$month}/";

        // Cari transaksi terakhir di bulan dan tahun ini
        $lastSale = static::where('invoice', 'LIKE', "{$prefix}%")
            ->orderBy('invoice', 'desc')
            ->first();

        $sequence = 1;

        if ($lastSale) {
            // Ambil angka terakhir. Contoh: IN/2026/03/00045
            $parts = explode('/', $lastSale->invoice);
            $lastSequence = (int) end($parts); // Mengambil '00045' dan mengubahnya jadi angka 45

            $sequence = $lastSequence + 1; // Menjadi 46
        }

        // Pad dengan angka 0 di depan agar selalu 5 digit: INV/2026/03/00046
        return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    public function paymentHistory()
    {
        return $this->hasMany(PaymentHistory::class, 'id_sales', 'id_sales')->latest();
    }
    public function lead()
    {
        return $this->belongsTo(LeadModel::class, 'id_lead', 'id_lead');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
    public function salesProduct()
    {
        return $this->hasMany(SalesProductModel::class, 'id_sales', 'id_sales');
    }
    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'id_branch', 'id_branch');
    }
    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'id_customer', 'id_customer');
    }
}
