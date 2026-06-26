<?php

namespace Database\Seeders;

use App\Models\ChannelModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            ['name' => 'WhatsApp Direct', 'code' => 'WA',  'icon' => 'bi-whatsapp'],
            ['name' => 'Instagram DM',    'code' => 'IG',  'icon' => 'bi-instagram'],
            ['name' => 'TikTok Ads',      'code' => 'TT',  'icon' => 'bi-tiktok'],
            ['name' => 'Facebook Ads',    'code' => 'FB',  'icon' => 'bi-facebook'],
            ['name' => 'Form Website',    'code' => 'WEB', 'icon' => 'bi-globe'],
            ['name' => 'Walk-In Toko',    'code' => 'WLK', 'icon' => 'bi-shop'],
            ['name' => 'Referral Teman',  'code' => 'REF', 'icon' => 'bi-people-fill'],
        ];

        foreach ($channels as $c) {
            ChannelModel::firstOrCreate(['code' => $c['code']], $c);
        }
    }
}
