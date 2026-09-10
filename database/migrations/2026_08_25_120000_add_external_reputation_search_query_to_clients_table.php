<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Query de texto Outscraper que sí trae reviews_per_score (p. ej. "Nombre, Ciudad").
            // Se rellena en el bootstrap (1ª sync); los syncs siguientes usan solo esta query (1 ficha).
            $table->string('external_reputation_search_query', 500)
                ->nullable()
                ->after('external_reputation_last_error');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('external_reputation_search_query');
        });
    }
};
