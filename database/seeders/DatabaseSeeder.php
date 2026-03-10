<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ImprovementReasonSeeder::class);
        $this->call(SectorSeeder::class);

        User::firstOrCreate(
            ['email' => 'reputalis2026@gmail.com'],  // ← TU email
            [
                'id' => 'a2027abe-89e0-40f5-b3b1-11351267c325',  // ← UUID fijo
                'name' => 'REPUTALIS',
                'fullname' => 'REPUTALIS SuperAdmin',
                'password' => Hash::make('Gironda2026'),  // ← TU pass
                'role' => User::ROLE_SUPERADMIN,
                'email_verified_at' => now(),
            ]
        );
    }
}