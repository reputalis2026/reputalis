<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Regla 1–1: cada empleado tendrá exactamente un token NFC.
        // Nota: en el estado actual del sistema no hay tokens con employee_id NULL,
        // así que podemos endurecer la columna a NOT NULL.
        DB::statement('ALTER TABLE nfctokens ALTER COLUMN employee_id SET NOT NULL');

        // Restricción UNIQUE por empleado.
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS nfctokens_employee_id_unique ON nfctokens (employee_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS nfctokens_employee_id_unique');
        // Volvemos a permitir NULL por compatibilidad (aunque el dominio lo trate como 1–1).
        DB::statement('ALTER TABLE nfctokens ALTER COLUMN employee_id DROP NOT NULL');
    }
};

