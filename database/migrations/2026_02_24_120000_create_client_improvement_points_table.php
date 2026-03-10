<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Puntos de mejora por cliente: título + relación con motivo base (código).
     */
    public function up(): void
    {
        Schema::create('client_improvement_points', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('client_id');
            $table->string('improvement_reason_code'); // waiting_time, sympathy, etc.
            $table->string('title'); // Título visible del punto de mejora
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'improvement_reason_code']);
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_improvement_points');
    }
};
