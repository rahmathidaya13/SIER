<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'id_user',
        'id_product',
        'id_branch',
        'deal_price',
        'remaining_bill',
        'paid_amount',
        'payment_status',
        'payment_method',
        'deal_at',
        'notes',
    ];
    protected $dates    = ["deleted_at"];
    protected $casts = [
        'deal_price' => 'integer',
        'remaining_bill' => 'integer',
        'paid_amount' => 'integer',
        'deal_at' => 'datetime',
    ];
    public function scopeAllowedForUser($query, User $user)
    {
        // Jika yang login Orang Pusat (Superadmin), BUKA SEMUA DATA!
        if ($user->hasRole('super_admin')) {
            return $query;
        }

        // Jika yang login SPV/Sales/Admin Cabang, KUNCI HANYA DI CABANG DIA!
        return $query->where('sales.id_branch', $user->id_branch);
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
    public function lead()
    {
        return $this->belongsTo(LeadModel::class, 'id_lead', 'id_lead');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'id_product', 'id_product');
    }
    public function branch()
    {
        return $this->belongsTo(BranchModel::class, 'id_branch', 'id_branch');
    }
}
