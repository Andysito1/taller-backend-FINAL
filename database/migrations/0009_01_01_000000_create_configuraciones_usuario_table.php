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
        Schema::create('configuraciones_usuario', function (Blueprint $table) {
    $table->id();
    $table->foreignId('id_cliente')->constrained('clientes')->onDelete('cascade');
    $table->enum('tema', ['claro', 'oscuro'])->default('claro');
    $table->boolean('notificaciones_activas')->default(true);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
