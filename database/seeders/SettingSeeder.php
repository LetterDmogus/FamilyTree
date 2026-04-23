<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site_name',
                'value' => 'Wise Mystical Tree',
                'type' => 'string',
                'description' => 'Nama identitas utama website silsilah',
            ],
            [
                'key' => 'max_spouses',
                'value' => '0',
                'type' => 'integer',
                'description' => 'Maksimal jumlah pasangan (0 untuk tak terbatas)',
            ],
            [
                'key' => 'allow_same_sex',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Izinkan menambahkan pasangan dengan gender yang sama',
            ],
            [
                'key' => 'allow_dead_login',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Izinkan anggota yang sudah meninggal untuk login',
            ],
            [
                'key' => 'allow_custom_metadata',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Izinkan penambahan metadata teks kustom secara bebas',
            ],
            [
                'key' => 'priority_limit',
                'value' => '10',
                'type' => 'integer',
                'description' => 'Limit tampilan data berdasarkan prioritas',
            ],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
