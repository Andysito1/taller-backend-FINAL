<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_reportes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_usuario')->constrained('usuarios'); // Quién generó el reporte
            $table->string('tipo_reporte'); // Ej: 'clientes_reservas', 'servicios_adquiridos'
            $table->json('filtros_aplicados')->nullable(); // Almacena fechas, meses o IDs filtrados
            $table->string('formato', 10)->default('xlsx'); // Siempre útil saber si fue Excel u otro
            $table->integer('total_registros')->default(0); // Para saber qué tan grande fue la data extraída
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_reportes');
    }
};