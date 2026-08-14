<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('google_place_id', 255)->nullable()->after('logo');
            $table->string('google_id', 255)->nullable()->after('google_place_id');
            $table->index('google_place_id');
        });

        // Copiar Place ID desde la configuración de encuesta (fuente antigua).
        if (Schema::hasTable('client_improvement_configs')
            && Schema::hasColumn('client_improvement_configs', 'google_place_id')
        ) {
            DB::statement("
                UPDATE clients AS c
                SET google_place_id = cic.google_place_id
                FROM client_improvement_configs AS cic
                WHERE cic.client_id = c.id
                  AND cic.google_place_id IS NOT NULL
                  AND TRIM(cic.google_place_id) <> ''
                  AND (c.google_place_id IS NULL OR TRIM(c.google_place_id) = '')
            ");
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['google_place_id']);
            $table->dropColumn(['google_place_id', 'google_id']);
        });
    }
};
