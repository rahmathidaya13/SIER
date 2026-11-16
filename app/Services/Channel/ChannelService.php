<?php

namespace App\Services\Channel;

use App\Models\ChannelModel;
use Illuminate\Support\Facades\DB;

class ChannelService
{
    public function store(array $validated, $user): ChannelModel
    {
        return DB::transaction(function () use ($validated, $user) {
            return ChannelModel::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'icon' => $validated['icon'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        });
    }

    public function update(ChannelModel $channel, array $validated, $user): ChannelModel
    {
        return DB::transaction(function () use ($channel, $validated, $user) {
            $channel->update([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'icon' => $validated['icon'],
                'updated_by' => $user->id,
            ]);
            return $channel;
        });
    }

    public function delete(ChannelModel $channel)
    {
        return DB::transaction(function () use ($channel) {
            $channel->delete();
        });
    }
}
