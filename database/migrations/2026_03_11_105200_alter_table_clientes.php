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
        // 1. Ampliar el ENUM de tipos de documento. Evitamos ->change() sobre un enum
        // existente: Laravel genera un ALTER COLUMN TYPE ... CHECK (...) combinado que
        // no es válido en Postgres (sí funciona en MySQL, pero no es portable). Como en
        // este punto de la migración la columna recién fue creada por la migración
        // anterior (sin datos que preservar), es seguro recrearla directamente.
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PAS'])
                ->nullable(false)
                ->after('id_usuario');
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
            $table->dropColumn('tipo_documento');
        });
        Schema::table('clientes', function (Blueprint $table) {
            $table->enum('tipo_documento', ['DNI', 'RUC'])->nullable(false)->after('id_usuario');
            $table->string('numero_documento', 11)->change();
        });
    }
};
