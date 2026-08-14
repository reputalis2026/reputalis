<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_external_reputation_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('client_id');
            $table->timestampTz('captured_at');
            $table->date('snapshot_date');
            $table->decimal('rating', 3, 2)->nullable();
            $table->unsignedInteger('reviews_total')->default(0);
            $table->unsignedInteger('stars_1')->default(0);
            $table->unsignedInteger('stars_2')->default(0);
            $table->unsignedInteger('stars_3')->default(0);
            $table->unsignedInteger('stars_4')->default(0);
            $table->unsignedInteger('stars_5')->default(0);
            $table->decimal('calculated_rating', 5, 4)->nullable();
            $table->string('source', 50)->default('outscraper');
            $table->jsonb('raw_payload')->nullable();
            $table->timestampsTz();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->index(['client_id', 'snapshot_date']);
            $table->index(['client_id', 'captured_at']);
        });

        Schema::create('client_external_reputation_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('client_id');
            $table->timestampTz('detected_at');
            $table->unsignedInteger('delta_stars_1')->default(0);
            $table->unsignedInteger('delta_stars_2')->default(0);
            $table->uuid('from_snapshot_id')->nullable();
            $table->uuid('to_snapshot_id')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->foreign('from_snapshot_id')
                ->references('id')
                ->on('client_external_reputation_snapshots')
                ->nullOnDelete();

            $table->foreign('to_snapshot_id')
                ->references('id')
                ->on('client_external_reputation_snapshots')
                ->nullOnDelete();

            $table->index(['client_id', 'detected_at']);
            $table->index(['client_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_external_reputation_alerts');
        Schema::dropIfExists('client_external_reputation_snapshots');
    }
};
