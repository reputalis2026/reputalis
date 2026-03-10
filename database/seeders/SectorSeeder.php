<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Farmacia', 'sort_order' => 1],
            ['name' => 'Herbolario', 'sort_order' => 2],
            ['name' => 'Parafarmacia', 'sort_order' => 3],
            ['name' => 'Centro de salud', 'sort_order' => 4],
            ['name' => 'Otro', 'sort_order' => 99],
        ];

        foreach ($defaults as $sector) {
            Sector::firstOrCreate(
                ['name' => $sector['name']],
                ['sort_order' => $sector['sort_order']]
            );
        }
    }
}
