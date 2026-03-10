<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renombra pharmacies → clients y pharmacy_id → client_id en todas las tablas.
     * Sin pérdida de datos.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
        });
        DB::statement('ALTER TABLE users RENAME COLUMN pharmacy_id TO client_id');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
        });
        DB::statement('ALTER TABLE employees RENAME COLUMN pharmacy_id TO client_id');

        Schema::table('nfctokens', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
        });
        DB::statement('ALTER TABLE nfctokens RENAME COLUMN pharmacy_id TO client_id');

        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
        });
        DB::statement('ALTER TABLE csat_surveys RENAME COLUMN pharmacy_id TO client_id');

        Schema::rename('pharmacies', 'clients');

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
        Schema::table('nfctokens', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
        });

        if (Schema::hasTable('pharmacysettings')) {
            Schema::rename('pharmacysettings', 'clientsettings');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        Schema::table('nfctokens', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        Schema::rename('clients', 'pharmacies');

        DB::statement('ALTER TABLE users RENAME COLUMN client_id TO pharmacy_id');
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('pharmacy_id')->references('id')->on('pharmacies')->nullOnDelete();
        });

        DB::statement('ALTER TABLE employees RENAME COLUMN client_id TO pharmacy_id');
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('pharmacy_id')->references('id')->on('pharmacies')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE nfctokens RENAME COLUMN client_id TO pharmacy_id');
        Schema::table('nfctokens', function (Blueprint $table) {
            $table->foreign('pharmacy_id')->references('id')->on('pharmacies')->cascadeOnDelete();
        });

        DB::statement('ALTER TABLE csat_surveys RENAME COLUMN client_id TO pharmacy_id');
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->foreign('pharmacy_id')->references('id')->on('pharmacies')->cascadeOnDelete();
        });

        if (Schema::hasTable('clientsettings')) {
            Schema::rename('clientsettings', 'pharmacysettings');
        }
    }
};
