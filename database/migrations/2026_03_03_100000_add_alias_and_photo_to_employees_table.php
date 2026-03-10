<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Añade alias (identificador corto para encuestas) y photo (imagen del empleado).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('alias', 100)->nullable()->after('name');
            $table->string('photo')->nullable()->after('alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['alias', 'photo']);
        });
    }
};
