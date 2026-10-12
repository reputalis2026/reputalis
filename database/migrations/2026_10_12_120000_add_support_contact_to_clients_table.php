<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Contacto que las farmacias de este distribuidor ven en Soporte.
            $table->string('support_phone', 30)->nullable()->after('telefono_cliente');
            $table->string('support_email')->nullable()->after('support_phone');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['support_phone', 'support_email']);
        });
    }
};
