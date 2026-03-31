<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modificar la tabla clientes para ampliar el ENUM de tipos de documento
        // Nota: DB::statement es necesario para modificar ENUMs en MySQL sin paquetes extra
        Schema::table('clientes', function (Blueprint $table) {
            // Primero cambiamos a string temporalmente o modificamos el enum directamente
             DB::statement("ALTER TABLE clientes MODIFY COLUMN tipo_documento ENUM('DNI', 'RUC', 'CE', 'PAS') NOT NULL");
        });

        // 2. Agregar columnas a la tabla mecanicos
        Schema::table('mecanicos', function (Blueprint $table) {
            $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PAS'])
                ->nullable() // Nullable por si ya existen mecánicos sin doc
                ->after('id_usuario');

            $table->string('numero_documento', 20) // Aumentamos a 20 para pasaportes/CE
                ->nullable()
                ->unique()
                ->after('tipo_documento');
        });
        
        // 3. Ampliar la longitud del numero_documento en clientes si era muy corto (era 11, lo subimos a 20 por seguridad para pasaportes)
         Schema::table('clientes', function (Blueprint $table) {
            $table->string('numero_documento', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mecanicos', function (Blueprint $table) {
            $table->dropColumn(['tipo_documento', 'numero_documento']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            // Revertir a solo DNI/RUC (Cuidado: esto fallará si hay datos CE/PAS)
            DB::statement("ALTER TABLE clientes MODIFY COLUMN tipo_documento ENUM('DNI', 'RUC') NOT NULL");
            $table->string('numero_documento', 11)->change();
        });
    }
};
