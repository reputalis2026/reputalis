<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->timestampTz('external_reputation_last_synced_at')->nullable()->after('google_id');
            $table->text('external_reputation_last_error')->nullable()->after('external_reputation_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'external_reputation_last_synced_at',
                'external_reputation_last_error',
            ]);
        });
    }
};
