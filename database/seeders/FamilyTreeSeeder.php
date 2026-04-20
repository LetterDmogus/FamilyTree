<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Relation;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class FamilyTreeSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Relation::truncate();
        UserProfile::truncate();
        User::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Ensure roles exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $memberRole = Role::firstOrCreate(['name' => 'member']);

        // 1. GENERASI 1: KAKEK & NENEK (Meninggal)
        $kakek = $this->createUser('Kakek Budi', 'Budi Santoso', 'M', '1940-01-01', 'Solo', true, false, '2015-12-25');
        $nenek = $this->createUser('Nenek Siti', 'Siti Aminah', 'F', '1945-05-15', 'Yogyakarta', false, false, '2018-01-10');
        $this->addSpouse($kakek, $nenek);

        // 2. GENERASI 2: ANAK-ANAK KAKEK (Hidup)
        $ayah = $this->createUser('Ayah Agus', 'Agus Santoso', 'M', '1975-08-08', 'Jakarta');
        $ayah->assignRole($adminRole); // Agus is Admin

        $ibu = $this->createUser('Ibu Maya', 'Maya Sari', 'F', '1978-02-22', 'Bandung');
        $this->addChild($kakek, $ayah);
        $this->addSpouse($ayah, $ibu);

        $paman = $this->createUser('Paman Bambang', 'Bambang Santoso', 'M', '1980-03-03', 'Solo');
        $bibi1 = $this->createUser('Bibi Sari', 'Sari Fatmawati', 'F', '1982-04-04', 'Semarang');
        $this->addChild($kakek, $paman);
        $this->addSpouse($paman, $bibi1);

        $bibi2 = $this->createUser('Bibi Wati', 'Wati Santoso', 'F', '1985-12-12', 'Solo');
        $this->addChild($kakek, $bibi2);

        // 3. GENERASI 3: CUCU-CUCU
        $cici = $this->createUser('saya', 'Cici Santoso', 'F', '2005-10-10', 'Batam');
        $didi = $this->createUser('Didi', 'Didi Santoso', 'M', '2008-12-12', 'Batam');
        $this->addChild($ayah, $cici);
        $this->addChild($ayah, $didi);

        $suamiCici = $this->createUser('Feri', 'Feri Hermawan', 'M', '2003-05-05', 'Batam');
        $this->addSpouse($cici, $suamiCici);

        $eko = $this->createUser('Eko', 'Eko Santoso', 'M', '2006-01-01', 'Semarang');
        $fani = $this->createUser('Fani', 'Fani Santoso', 'F', '2009-05-05', 'Semarang');
        $this->addChild($paman, $eko);
        $this->addChild($paman, $fani);

        $istriEko = $this->createUser('Gita', 'Gita Permata', 'F', '2007-02-02', 'Semarang');
        $this->addSpouse($eko, $istriEko);

        // 4. GENERASI 4: CICIT
        $hani = $this->createUser('Hani', 'Hani Santoso', 'F', '2025-01-01', 'Semarang');
        $this->addChild($eko, $hani);

        $gina = $this->createUser('Gina', 'Gina Putri', 'F', '2018-09-09', 'Solo');
        $this->addChild($bibi2, $gina);
    }

    private function createUser($name, $fullName, $gender, $birthDate, $birthPlace, $isHead = false, $isAlive = true, $deathDate = null)
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
            'is_alive' => $isAlive,
            'death_date' => $deathDate,
        ]);

        $user->assignRole('member');

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
