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
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            // 'en_espera' es el estado inicial, 'aprobado' cuando el cliente/admin valida.
            $table->enum('validacion_diagnostico', ['en_espera', 'aprobado', 'aclaracion'])
                  ->default('en_espera')
                  ->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropColumn('validacion_diagnostico');
        });
    }
};