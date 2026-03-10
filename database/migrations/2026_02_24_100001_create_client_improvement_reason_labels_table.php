<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etiquetas personalizadas de motivos de mejora por cliente (para distribuidores).
     * Mantiene el code base; solo cambia el texto visible por cliente.
     */
    public function up(): void
    {
        Schema::create('client_improvement_reason_labels', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('client_id');
            $table->string('improvement_reason_code'); // waiting_time, sympathy, etc.
            $table->string('label'); // texto visible para ese cliente
            $table->timestamps();

            $table->unique(['client_id', 'improvement_reason_code']);
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_improvement_reason_labels');
    }
};
