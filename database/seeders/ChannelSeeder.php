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
            ['name' => 'WhatsApp', 'code' => 'WA',  'icon' => 'bi-whatsapp text-success'],
            ['name' => 'Instagram',    'code' => 'IG',  'icon' => 'bi-instagram text-danger'],
            ['name' => 'TikTok',      'code' => 'TT',  'icon' => 'bi-tiktok text-dark'],
            ['name' => 'Facebook',    'code' => 'FB',  'icon' => 'bi-facebook text-primary'],
            ['name' => 'Twitter',    'code' => 'TW',  'icon' => 'bi-twitter-x text-dark'],
            ['name' => 'Youtube',     'code' => 'YT',  'icon' => 'bi-youtube text-danger'],
            ['name' => 'Telegram',    'code' => 'TG',  'icon' => 'bi-telegram text-primary'],
            ['name' => 'Walk-In Toko',    'code' => 'WLK', 'icon' => 'bi-shop text-success'],
            ['name' => 'Form Website',    'code' => 'WEB', 'icon' => 'bi-globe text-info'],
            ['name' => 'Form Telepon',    'code' => 'TEL', 'icon' => 'bi-telephone text-success'],
            ['name' => 'Form Email',    'code' => 'EMAIL', 'icon' => 'bi-envelope text-danger'],
            ['name' => 'Broadcast',    'code' => 'CALL', 'icon' => 'bi-megaphone text-warning'],
            ['name' => 'Referral Teman',  'code' => 'REF', 'icon' => 'bi-people-fill text-info'],
        ];

        foreach ($channels as $c) {
            ChannelModel::firstOrCreate(['code' => $c['code']], $c);
        }
    }
}
