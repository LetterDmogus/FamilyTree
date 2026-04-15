<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari User Agus (Ayah Agus)
        $agus = User::where('name', 'Ayah Agus')->first();
        if ($agus) {
            $agus->syncRoles(['superadmin']);
        }

        // Cari User Cici
        $cici = User::where('name', 'Cici')->first();
        if ($cici) {
            $cici->syncRoles(['admin']);
        }

        // Assign 'member' role to all other users
        User::whereNotIn('name', ['Ayah Agus', 'Cici'])->get()->each(function ($user) {
            $user->assignRole('member');
        });
    }
}
