<?php

namespace Database\Seeders;

use App\Models\MasterAdditionalField;
use App\Models\MasterSocialMedia;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Social Media Platforms with Icons
        $socialMedias = [
            [
                'name' => 'Instagram',
                'prefix' => '@',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/instagram.svg',
            ],
            [
                'name' => 'TikTok',
                'prefix' => '@',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/tiktok.svg',
            ],
            [
                'name' => 'X (Twitter)',
                'prefix' => '@',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/x.svg',
            ],
            [
                'name' => 'Facebook',
                'prefix' => 'none',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/facebook.svg',
            ],
            [
                'name' => 'LinkedIn',
                'prefix' => 'none',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/linkedin.svg',
            ],
            [
                'name' => 'WhatsApp',
                'prefix' => 'wa.me/',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/whatsapp.svg',
            ],
            [
                'name' => 'YouTube',
                'prefix' => '@',
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/youtube.svg',
            ],
        ];

        foreach ($socialMedias as $sm) {
            MasterSocialMedia::updateOrCreate(['name' => $sm['name']], $sm);
        }

        // 2. Seed Additional Profile Fields
        $fields = [
            // Group: Identitas
            ['name' => 'NIK', 'group_name' => 'Identitas', 'icon_key' => 'user', 'input_type' => 'text'],
            [
                'name' => 'Agama',
                'group_name' => 'Identitas',
                'icon_key' => 'book',
                'input_type' => 'select',
                'options' => ['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu'],
            ],
            
            // Group: Profil Profesional
            ['name' => 'Pekerjaan', 'group_name' => 'Profil', 'icon_key' => 'briefcase', 'input_type' => 'text'],
            [
                'name' => 'Pendidikan',
                'group_name' => 'Profil',
                'icon_key' => 'book',
                'input_type' => 'select',
                'options' => ['SD', 'SMP', 'SMA/SMK', 'D1/D2/D3', 'S1', 'S2', 'S3', 'Tidak Sekolah'],
            ],
            ['name' => 'Gelar', 'group_name' => 'Profil', 'icon_key' => 'book', 'input_type' => 'text'],
            ['name' => 'Tempat Bekerja', 'group_name' => 'Profil', 'icon_key' => 'home', 'input_type' => 'text'],
            
            // Group: Medis
            [
                'name' => 'Golongan Darah',
                'group_name' => 'Medis',
                'icon_key' => 'heart',
                'input_type' => 'select',
                'options' => ['A', 'B', 'AB', 'O', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'],
            ],
            
            // Group: Kontak
            ['name' => 'Nomor Telepon', 'group_name' => 'Kontak', 'icon_key' => 'phone', 'input_type' => 'text'],
            ['name' => 'Email Alt', 'group_name' => 'Kontak', 'icon_key' => 'mail', 'input_type' => 'text'],
            
            // Group: Narasi
            ['name' => 'Hobi', 'group_name' => 'Narasi', 'icon_key' => 'heart', 'input_type' => 'text'],
            ['name' => 'Cerita Hidup', 'group_name' => 'Narasi', 'icon_key' => 'book', 'input_type' => 'textarea'],
        ];

        foreach ($fields as $field) {
            MasterAdditionalField::updateOrCreate(['name' => $field['name']], $field);
        }
    }
}
