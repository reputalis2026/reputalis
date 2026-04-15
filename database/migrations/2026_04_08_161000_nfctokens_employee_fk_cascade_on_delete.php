<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * employee_id pasó a NOT NULL + único; la FK seguía en ON DELETE SET NULL,
     * lo que hace imposible borrar un empleado (PostgreSQL intenta poner employee_id a NULL).
     */
    public function up(): void
    {
        Schema::table('nfctokens', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('nfctokens', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nfctokens', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('nfctokens', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }
};
