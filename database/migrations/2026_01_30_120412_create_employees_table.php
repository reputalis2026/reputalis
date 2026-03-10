<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea la tabla employees con UUID y FK a pharmacies (debe ejecutarse después de create_pharmacies).
     */
    public function up(): void
    {
        Schema::dropIfExists('employees');

        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('pharmacy_id');
            $table->string('name');
            $table->string('position')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('pharmacy_id')
                ->references('id')
                ->on('pharmacies')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
