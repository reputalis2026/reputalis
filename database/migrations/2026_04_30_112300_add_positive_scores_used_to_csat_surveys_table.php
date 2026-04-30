<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->jsonb('positive_scores_used')->nullable()->after('improvement_option_id');
        });

        DB::statement(<<<'SQL'
            UPDATE csat_surveys AS cs
            SET positive_scores_used = COALESCE(
                (
                    SELECT cic.positive_scores
                    FROM client_improvement_configs AS cic
                    WHERE cic.client_id = cs.client_id
                      AND cic.updated_at <= cs.created_at
                    ORDER BY cic.updated_at DESC
                    LIMIT 1
                ),
                '[4,5]'::jsonb
            )
            WHERE cs.positive_scores_used IS NULL
        SQL);
    }

    public function down(): void
    {
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->dropColumn('positive_scores_used');
        });
    }
};
