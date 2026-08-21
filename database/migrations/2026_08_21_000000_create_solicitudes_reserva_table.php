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
        Schema::create('solicitudes_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tipo_documento')->constrained('tipos_documento');
            $table->string('numero_documento', 20);
            $table->string('correo', 150);
            $table->string('telefono', 15);
            $table->string('vehiculo_marca', 50);
            $table->string('vehiculo_modelo', 50);
            $table->integer('vehiculo_anio');
            $table->text('problema');
            $table->enum('estado', ['pendiente', 'atendida', 'rechazada'])->default('pendiente');
            $table->string('motivo_rechazo', 255)->nullable();
            $table->timestamps();

            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_reserva');
    }
};
