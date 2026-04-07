<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_improvement_configs', function (Blueprint $table) {
            $table->string('survey_question_text')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('client_improvement_configs', function (Blueprint $table) {
            $table->dropColumn('survey_question_text');
        });
    }
};
