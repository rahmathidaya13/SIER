<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\LeadModel;
use App\Models\ChannelModel;
use App\Models\ProductModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LeadTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::whereNotNull('id_branch')->first();

        if (!$user) {
            $this->command->error(
                'User dengan cabang belum tersedia.'
            );

            return;
        }

        $channel = ChannelModel::pluck('id_channel')->all();

        if (!$channel) {
            $this->command->error(
                'Data channel belum tersedia.'
            );

            return;
        }

        $products = ProductModel::limit(10)
            ->pluck('id_product')
            ->all();

        if (empty($products)) {
            $this->command->error(
                'Data produk belum tersedia.'
            );

            return;
        }

        $statuses = [
            'NEW',
            'FOLLOW_UP',
            'CLOSED_WON',
            'CLOSED_LOST',
        ];

        $temperatures = [
            'COLD',
            'WARM',
            'HOT',
        ];

        DB::transaction(function () use (
            $user,
            $channel,
            $products,
            $statuses,
            $temperatures
        ) {
            $totalLeads = 100;


            for ($i = 1; $i <= $totalLeads; $i++) {
                $channelCount = count($channel);
                $status =  $statuses[array_rand($statuses)];

                if ($status === 'CLOSED_WON') {
                    $temperature = 'HOT';
                } else {
                    $temperature = $temperatures[array_rand($temperatures)];
                }

                $this->command->info(
                    "Lead {$i} | Status: {$status} | Temperature: {$temperature}"
                );

                $lead = LeadModel::create([
                    'id_user'       => $user->id,
                    'id_branch'     => $user->id_branch,
                    'id_channel'    => $channel[($i - 1) % $channelCount],
                    'name'          => "Lead Testing {$i}",
                    'email'         => "leadtest{$i}@example.com",
                    'phone'         => '08123456' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'status'        => $status,
                    'temperature'   => $temperature,
                    'customer_type' => 'NEW_CUSTOMER',
                    'notes'         => 'Data khusus pengujian',
                    'closed_at'     => $status === 'CLOSED_WON' ? now()->toDateTimeString() : null,
                ]);

                $selectedProducts = collect($products)
                    ->random(min(2, count($products)))
                    ->map(fn($id) => [
                        'id_product' => $id,
                    ])
                    ->all();

                $lead->leadProduct()->createMany(
                    $selectedProducts
                );
            }
        });

        $this->command->info(
            "Berhasil membuat lead testing."
        );
    }
}
