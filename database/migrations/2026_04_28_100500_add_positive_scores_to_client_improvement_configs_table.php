<?php

use App\Models\ClientImprovementConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_improvement_configs', function (Blueprint $table) {
            $table->jsonb('positive_scores')->nullable();
        });

        DB::table('client_improvement_configs')->update([
            'positive_scores' => json_encode(ClientImprovementConfig::defaultPositiveScores()),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('client_improvement_configs', function (Blueprint $table) {
            $table->dropColumn('positive_scores');
        });
    }
};
