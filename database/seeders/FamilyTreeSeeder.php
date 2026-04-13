<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Relation;
use Illuminate\Support\Facades\DB;

class FamilyTreeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Relation::truncate();
        UserProfile::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // 1. GENERASI 1: KAKEK & NENEK
        $kakek = $this->createUser('Kakek Budi', 'Budi Santoso', 'M', '1950-01-01', 'Solo', true);
        $nenek = $this->createUser('Nenek Siti', 'Siti Aminah', 'F', '1955-05-15', 'Yogyakarta');
        $this->addSpouse($kakek, $nenek);

        // 2. GENERASI 2: ANAK-ANAK KAKEK
        
        // --- Cabang 1: Ayah Agus (Anak Sulung) ---
        $ayah = $this->createUser('Ayah Agus', 'Agus Santoso', 'M', '1980-08-08', 'Jakarta');
        $ibu = $this->createUser('Ibu Maya', 'Maya Sari', 'F', '1982-02-22', 'Bandung');
        $this->addChild($kakek, $ayah);
        $this->addSpouse($ayah, $ibu);

        // --- Cabang 2: Paman Bambang (Anak Tengah) ---
        $paman = $this->createUser('Paman Bambang', 'Bambang Santoso', 'M', '1983-03-03', 'Solo');
        $bibi1 = $this->createUser('Bibi Sari', 'Sari Fatmawati', 'F', '1985-04-04', 'Semarang');
        $this->addChild($kakek, $paman);
        $this->addSpouse($paman, $bibi1);

        // --- Cabang 3: Bibi Wati (Anak Bungsu) ---
        $bibi2 = $this->createUser('Bibi Wati', 'Wati Santoso', 'F', '1988-12-12', 'Solo');
        $this->addChild($kakek, $bibi2);

        // 3. GENERASI 3: CUCU-CUCU KAKEK

        // --- Cucu dari Agus (Viewer Context: Cici) ---
        $cici = $this->createUser('Cici', 'Cici Santoso', 'F', '2010-10-10', 'Batam');
        $didi = $this->createUser('Didi', 'Didi Santoso', 'M', '2012-12-12', 'Batam');
        $this->addChild($ayah, $cici);
        $this->addChild($ayah, $didi);

        // NEW: Suami Cici
        $suamiCici = $this->createUser('Feri', 'Feri Hermawan', 'M', '2008-05-05', 'Batam');
        $this->addSpouse($cici, $suamiCici);

        // Update Cici with rich info for testing
        $cici->profile->update([
            'social_media' => [
                ['platform_name' => 'Instagram', 'username' => 'cici.arts'],
                ['platform_name' => 'TikTok', 'username' => 'cici_dance']
            ],
            'additional_info' => [
                'pekerjaan' => 'Desainer Grafis Junior',
                'tempat_bekerja' => 'Studio Kreatif Batam',
                'cerita' => 'Cici adalah cucu kesayangan kakek yang hobi menggambar.',
            ]
        ]);

        // --- Cucu dari Bambang (Sepupu) ---
        $eko = $this->createUser('Eko', 'Eko Santoso', 'M', '2011-01-01', 'Semarang');
        $fani = $this->createUser('Fani', 'Fani Santoso', 'F', '2014-05-05', 'Semarang');
        $this->addChild($paman, $eko);
        $this->addChild($paman, $fani);

        // NEW: Istri Eko
        $istriEko = $this->createUser('Gita', 'Gita Permata', 'F', '2012-02-02', 'Semarang');
        $this->addSpouse($eko, $istriEko);

        // 4. GENERASI 4: CICIT
        
        // NEW: Anak Eko & Gita
        $hani = $this->createUser('Hani', 'Hani Santoso', 'F', '2025-01-01', 'Semarang');
        $this->addChild($eko, $hani);

        // --- Cucu dari Wati (Keponakan) ---
        $gina = $this->createUser('Gina', 'Gina Putri', 'F', '2018-09-09', 'Solo');
        $this->addChild($bibi2, $gina);
    }

    private function createUser($name, $fullName, $gender, $birthDate, $birthPlace, $isHead = false)
    {
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.com',
            'password' => bcrypt('password'),
        ]);

        UserProfile::create([
            'user_id' => $user->id,
            'full_name' => $fullName,
            'gender' => $gender,
            'birth_date' => $birthDate,
            'birth_place' => $birthPlace,
            'is_family_head' => $isHead,
        ]);

        return $user;
    }

    private function addSpouse($user1, $user2)
    {
        Relation::create(['user_id' => $user1->id, 'related_user_id' => $user2->id, 'type' => 'spouse', 'is_blood' => false]);
        Relation::create(['user_id' => $user2->id, 'related_user_id' => $user1->id, 'type' => 'spouse', 'is_blood' => false]);
    }

    private function addChild($parent, $child)
    {
        Relation::create(['user_id' => $parent->id, 'related_user_id' => $child->id, 'type' => 'child', 'is_blood' => true]);
    }
}
