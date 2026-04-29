<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_active')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_active')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('last_login_at');
        });
    }
};
