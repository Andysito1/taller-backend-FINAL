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
        Schema::create('etapas_servicio', function (Blueprint $table) {
    $table->id();
    $table->foreignId('id_orden')->constrained('ordenes_servicio')->onDelete('cascade');
    $table->enum('etapa', ['diagnostico', 'reparacion', 'pruebas', 'finalizacion']);
    $table->enum('estado', ['pendiente', 'en_proceso', 'completado'])->default('pendiente');
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
