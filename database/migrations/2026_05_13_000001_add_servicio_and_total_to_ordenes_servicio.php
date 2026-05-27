<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            // Relación con el servicio específico
            $table->foreignId('id_servicio')->nullable()->after('id_mecanico')->constrained('servicios');
            
            // Costo total del servicio (Costo base + posibles cálculos adicionales)
            $table->decimal('costo_total', 10, 2)->default(0.00)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('ordenes_servicio', function (Blueprint $table) {
            $table->dropForeign(['id_servicio']);
            $table->dropColumn(['id_servicio', 'costo_total']);
        });
    }
};