<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS csat_surveys_created_at_idx ON csat_surveys (created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS csat_surveys_client_created_at_idx ON csat_surveys (client_id, created_at)');
        DB::statement('CREATE INDEX IF NOT EXISTS csat_surveys_client_created_at_score_idx ON csat_surveys (client_id, created_at, score)');
        DB::statement('CREATE INDEX IF NOT EXISTS clients_is_active_namecommercial_idx ON clients (is_active, namecommercial)');
        DB::statement('CREATE INDEX IF NOT EXISTS clients_is_active_fecha_fin_idx ON clients (is_active, fecha_fin)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS csat_surveys_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS csat_surveys_client_created_at_idx');
        DB::statement('DROP INDEX IF EXISTS csat_surveys_client_created_at_score_idx');
        DB::statement('DROP INDEX IF EXISTS clients_is_active_namecommercial_idx');
        DB::statement('DROP INDEX IF EXISTS clients_is_active_fecha_fin_idx');
    }
};
