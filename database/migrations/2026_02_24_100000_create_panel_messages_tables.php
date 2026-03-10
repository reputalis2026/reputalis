<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mensajes/notificaciones del panel (pendiente activación, cliente activado, etc.).
     */
    public function up(): void
    {
        Schema::create('panel_messages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('type'); // client_pending_activation, client_activated, ...
            $table->uuid('sender_user_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();

            $table->foreign('sender_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });

        Schema::create('panel_message_recipients', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('panel_message_id');
            $table->uuid('user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['panel_message_id', 'user_id']);
            $table->foreign('panel_message_id')->references('id')->on('panel_messages')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panel_message_recipients');
        Schema::dropIfExists('panel_messages');
    }
};
