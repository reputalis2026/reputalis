<?php

namespace Database\Seeders;

use App\Models\ImprovementReason;
use Illuminate\Database\Seeder;

class ImprovementReasonSeeder extends Seeder
{
    /**
     * Motivos de mejora por defecto para encuestas CSAT (los textos se resuelven por i18n en frontend).
     */
    public function run(): void
    {
        $reasons = [
            'waiting_time',
            'sympathy',
            'information',
            'product_availability',
            'other',
        ];

        foreach ($reasons as $code) {
            ImprovementReason::firstOrCreate(
                ['code' => $code],
                ['is_active' => true]
            );
        }
    }
}
