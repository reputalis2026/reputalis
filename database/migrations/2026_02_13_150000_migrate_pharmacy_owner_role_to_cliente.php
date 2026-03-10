<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'pharmacy_owner')
            ->update(['role' => 'cliente']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'cliente')
            ->update(['role' => 'pharmacy_owner']);
    }
};
