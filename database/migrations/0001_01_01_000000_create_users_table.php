
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration {
    public function up(): void {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('email')->unique();
            $table->string('password');
            $table->string('fullname');
            $table->enum('role', ['superadmin', 'admin', 'pharmacy_owner', 'employee'])->default('employee');
            $table->uuid('pharmacy_id')->nullable()->index();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('pharmacy_id')->references('id')->on('pharmacies');
        });
        // SUPERADMIN AUTOMÁTICO
        $superadminId = DB::raw('gen_random_uuid()');
        DB::table('users')->insert([
            'id' => $superadminId,
            'email' => 'reputalis2026@gmail.com',
            'password' => Hash::make('Gironda2026'),
            'fullname' => 'Super Administrador REPUTALIS',
            'role' => 'superadmin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Pharmacy demo para superadmin
        $pharmacyId = DB::raw('gen_random_uuid()');
        DB::table('pharmacies')->insert([
            'id' => $pharmacyId,
            'code' => 'DEMO001',
            'namecommercial' => 'Farmacia Demo',
            'fiscalname' => 'Farmacia Demo SL',
            'fiscalnif' => 'B12345678',
            'fiscaladdress' => 'Calle Demo 1',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('users')->where('email', 'admin@reputalis.com')->update(['pharmacy_id' => $pharmacyId]);
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
