<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * El Carnet de Extranjería (CE) peruano tiene 9 dígitos exactos.
     * La migración original lo dejó solo con longitud_maxima (12), sin exigir
     * una longitud exacta como sí ocurre con DNI (8) y RUC (11).
     */
    public function up(): void
    {
        DB::table('tipos_documento')
            ->where('abreviatura', 'CE')
            ->update(['longitud_exacta' => 9, 'longitud_maxima' => 9]);
    }

    public function down(): void
    {
        DB::table('tipos_documento')
            ->where('abreviatura', 'CE')
            ->update(['longitud_exacta' => null, 'longitud_maxima' => 12]);
    }
};
