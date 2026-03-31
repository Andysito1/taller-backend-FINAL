<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_notificacion')->constrained('notificaciones')->onDelete('cascade');
            $table->json('payload_enviado'); // Registro exacto de lo que se mandó
            $table->string('canal'); // 'fcm' o 'pusher'
            $table->boolean('exito')->default(false);
            $table->text('error_mensaje')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_notificaciones');
    }
};