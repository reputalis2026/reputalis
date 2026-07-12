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
            $table->string('google_place_id', 255)->nullable();
            $table->string('google_review_message', 500)->nullable();
            $table->string('google_review_message_es', 500)->nullable();
            $table->string('google_review_message_pt', 500)->nullable();
            $table->string('google_review_message_en', 500)->nullable();
        });

        $defaults = ClientImprovementConfig::defaultGoogleReviewMessages();

        DB::table('client_improvement_configs')->update([
            'google_review_message' => $defaults['es'],
            'google_review_message_es' => $defaults['es'],
            'google_review_message_pt' => $defaults['pt'],
            'google_review_message_en' => $defaults['en'],
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('client_improvement_configs', function (Blueprint $table) {
            $table->dropColumn([
                'google_place_id',
                'google_review_message',
                'google_review_message_es',
                'google_review_message_pt',
                'google_review_message_en',
            ]);
        });
    }
};
