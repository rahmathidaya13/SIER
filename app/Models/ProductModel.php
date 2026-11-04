<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ProductModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'products';
    protected $primaryKey = 'id_product';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'updated_by',
        'product_code',
        'source',
        'name',
        'slug',
        'category',
        'type_item',
        'item_condition',
        'link',
        'image_link',
        'image_path',
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

    public static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->product_code)) {
                $product->product_code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode()
    {
        do {
            $randomNumber = str_pad(mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            $code = $randomNumber;
        } while (self::where('product_code', $code)->exists());
        // Kembalikan kode yang sudah dipastikan unik
        return $code;
    }

    public function activePrice()
    {
        return $this->hasOne(ProductPriceModel::class, 'id_product', 'id_product')
            ->where('status', 'published') // Hanya ambil yang dipublish
            ->where(function ($query) {
                // Pastikan belum kedaluwarsa
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->latest(); // Jika ada beberapa, ambil yang paling baru dibuat
    }
    public function prices()
    {
        return $this->hasMany(ProductPriceModel::class, 'id_product', 'id_product')->orderBy('created_at', 'desc');
    }
    public function leads()
    {
        return $this->hasMany(LeadModel::class, 'id_product', 'id_product');
    }
    public function leadProduct()
    {
        return $this->hasMany(LeadProductModel::class, 'id_product', 'id_product');
    }
    public function sales()
    {
        return $this->hasMany(SalesModel::class);
    }
    public function salesProduct()
    {
        return $this->hasMany(SalesProductModel::class, 'id_product', 'id_product');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
