<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Opciones/respuestas de cada punto de mejora del cliente.
     */
    public function up(): void
    {
        Schema::create('client_improvement_point_options', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('client_improvement_point_id');
            $table->string('label'); // Texto de la opción
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('client_improvement_point_id')
                ->references('id')
                ->on('client_improvement_points')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_improvement_point_options');
    }
};
