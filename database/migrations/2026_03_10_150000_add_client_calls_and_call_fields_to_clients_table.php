<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'last_call_at')) {
                $table->timestampTz('last_call_at')->nullable()->after('logo');
            }
            if (! Schema::hasColumn('clients', 'next_call_at')) {
                $table->timestampTz('next_call_at')->nullable()->after('last_call_at');
            }
        });

        if (! Schema::hasTable('client_calls')) {
            Schema::create('client_calls', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('client_id');
                $table->timestampTz('called_at');
                $table->text('notes')->nullable();
                $table->timestampsTz();

                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->onDelete('cascade');

                $table->index(['client_id', 'called_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'next_call_at')) {
                $table->dropColumn('next_call_at');
            }
            if (Schema::hasColumn('clients', 'last_call_at')) {
                $table->dropColumn('last_call_at');
            }
        });

        Schema::dropIfExists('client_calls');
    }
};

