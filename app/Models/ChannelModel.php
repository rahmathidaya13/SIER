<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChannelModel extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    protected $table = 'channels';
    protected $primaryKey = 'id_channel';
    public $incrementing = false; // Karena pakai UUID
    protected $keyType = 'string';
    protected $fillable = [
        'created_by',
        'updated_by',
        'name',
        'code',
        'icon',
    ];
    protected $dates    = ["deleted_at"];
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
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id')->withTrashed();
    }
    public function leads()
    {
        return $this->hasMany(LeadModel::class, 'id_channel', 'id_channel');
    }
}
