<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Almacena la opción elegida cuando el usuario selecciona una respuesta en la encuesta negativa.
     */
    public function up(): void
    {
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->uuid('improvement_point_option_id')->nullable()->after('improvementreason_id');
        });

        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->foreign('improvement_point_option_id')
                ->references('id')
                ->on('client_improvement_point_options')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->dropForeign(['improvement_point_option_id']);
        });
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->dropColumn('improvement_point_option_id');
        });
    }
};
