<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('csat_surveys');

        Schema::create('csat_surveys', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('pharmacy_id');
            $table->uuid('employee_id')->nullable();
            $table->smallInteger('score'); // 1–5
            $table->uuid('improvementreason_id')->nullable();
            $table->string('locale_used')->nullable();
            $table->string('device_hash')->nullable();
            $table->timestamps();

            $table->foreign('pharmacy_id')
                ->references('id')
                ->on('pharmacies')
                ->cascadeOnDelete();
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
            $table->foreign('improvementreason_id')
                ->references('id')
                ->on('improvementreasons')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csat_surveys');
    }
};
