<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade campos de facturación y administrador a clients y users.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('nif', 20)->nullable()->after('namecommercial');
            $table->string('razon_social')->nullable()->after('nif');
            $table->string('calle')->nullable()->after('razon_social');
            $table->string('pais', 100)->default('España')->after('calle');
            $table->string('codigo_postal', 20)->nullable()->after('pais');
            $table->string('ciudad', 100)->nullable()->after('codigo_postal');
            $table->string('sector', 50)->default('Farmacia')->after('ciudad');
            $table->string('telefono_negocio', 30)->nullable()->after('sector');
            $table->string('telefono_cliente', 30)->nullable()->after('telefono_negocio');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('dni', 20)->nullable()->after('fullname');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'nif', 'razon_social', 'calle', 'pais', 'codigo_postal',
                'ciudad', 'sector', 'telefono_negocio', 'telefono_cliente',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dni');
        });
    }
};
