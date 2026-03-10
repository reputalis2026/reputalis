<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Eliminar tablas del modelo anterior (puntos por motivo base).
     */
    public function up(): void
    {
        Schema::dropIfExists('client_improvement_point_options');
        Schema::dropIfExists('client_improvement_points');
    }

    public function down(): void
    {
        // Recrear en migraciones anteriores si se hace rollback
    }
};
