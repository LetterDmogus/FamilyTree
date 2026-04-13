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
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/instagram.svg'
            ],
            [
                'name' => 'TikTok', 
                'prefix' => '@', 
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/tiktok.svg'
            ],
            [
                'name' => 'X (Twitter)', 
                'prefix' => '@', 
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/x.svg'
            ],
            [
                'name' => 'Facebook', 
                'prefix' => 'none', 
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/facebook.svg'
            ],
            [
                'name' => 'LinkedIn', 
                'prefix' => 'none', 
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/linkedin.svg'
            ],
            [
                'name' => 'WhatsApp', 
                'prefix' => 'wa.me/', 
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/whatsapp.svg'
            ],
            [
                'name' => 'YouTube', 
                'prefix' => '@', 
                'icon_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v11/icons/youtube.svg'
            ],
        ];

        foreach ($socialMedias as $sm) {
            MasterSocialMedia::updateOrCreate(['name' => $sm['name']], $sm);
        }

        // 2. Seed Additional Profile Fields
        $fields = [
            ['name' => 'Pekerjaan', 'icon_key' => 'briefcase', 'input_type' => 'text'],
            ['name' => 'Tempat Bekerja', 'icon_key' => 'home', 'input_type' => 'text'],
            [
                'name' => 'Agama', 
                'icon_key' => 'book', 
                'input_type' => 'select', 
                'options' => ['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu']
            ],
            ['name' => 'Nomor Telepon', 'icon_key' => 'phone', 'input_type' => 'text'],
            ['name' => 'Email', 'icon_key' => 'mail', 'input_type' => 'text'],
            ['name' => 'Tempat Tinggal', 'icon_key' => 'home', 'input_type' => 'textarea'],
            ['name' => 'Hobi', 'icon_key' => 'heart', 'input_type' => 'text'],
            ['name' => 'Cerita Hidup', 'icon_key' => 'book', 'input_type' => 'textarea'],
        ];

        foreach ($fields as $field) {
            MasterAdditionalField::updateOrCreate(['name' => $field['name']], $field);
        }
    }
}
