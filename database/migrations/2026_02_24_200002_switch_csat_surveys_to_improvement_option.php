<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sustituir improvement_point_option_id por improvement_option_id (nueva tabla).
     */
    public function up(): void
    {
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->uuid('improvement_option_id')->nullable()->after('improvementreason_id');
            $table->foreign('improvement_option_id')
                ->references('id')
                ->on('client_improvement_options')
                ->nullOnDelete();
        });

        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->dropForeign(['improvement_point_option_id']);
            $table->dropColumn('improvement_point_option_id');
        });
    }

    public function down(): void
    {
        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->uuid('improvement_point_option_id')->nullable()->after('improvementreason_id');
            $table->foreign('improvement_point_option_id')
                ->references('id')
                ->on('client_improvement_point_options')
                ->nullOnDelete();
        });

        Schema::table('csat_surveys', function (Blueprint $table) {
            $table->dropForeign(['improvement_option_id']);
            $table->dropColumn('improvement_option_id');
        });
    }
};
