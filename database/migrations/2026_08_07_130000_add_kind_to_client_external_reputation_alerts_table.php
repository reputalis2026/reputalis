<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_external_reputation_alerts', function (Blueprint $table) {
            $table->string('kind', 32)->default('negative_increase')->after('detected_at');
            $table->integer('delta_reviews_total')->default(0)->after('delta_stars_2');
        });
    }

    public function down(): void
    {
        Schema::table('client_external_reputation_alerts', function (Blueprint $table) {
            $table->dropColumn(['kind', 'delta_reviews_total']);
        });
    }
};
