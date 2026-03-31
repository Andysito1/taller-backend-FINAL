<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar columnas comunes a la tabla 'usuarios'
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->after('password');
            $table->string('direccion', 255)->nullable()->after('telefono');
            $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PAS'])->nullable()->after('direccion');
            $table->string('numero_documento', 20)->nullable()->unique()->after('tipo_documento');
        });

        // (OPCIONAL) AQUÍ DEBERÍAS MIGRAR LA DATA EXISTENTE DE CLIENTES/MECANICOS A USUARIOS SI TIENES DATOS REALES
        // DB::statement("UPDATE usuarios u JOIN clientes c ON u.id = c.id_usuario SET u.telefono = c.telefono, u.direccion = c.direccion ...");

        // 2. Limpiar tabla 'clientes' (ya no necesita estos campos)
        Schema::table('clientes', function (Blueprint $table) {
            // Nota: En algunas DBs debes eliminar dependencias de claves foráneas o índices antes de borrar columnas
            $table->dropColumn(['telefono', 'direccion', 'tipo_documento', 'numero_documento']);
        });

        // 3. Limpiar tabla 'mecanicos'
        Schema::table('mecanicos', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'direccion', 'tipo_documento', 'numero_documento']);
        });
    }

    public function down(): void
    {
        // Revertir es complejo porque implica devolver columnas y datos.
        // Aquí solo borramos las de usuario y recreamos las hijas vacías.
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'direccion', 'tipo_documento', 'numero_documento']);
        });

        Schema::table('clientes', function (Blueprint $table) {
             $table->string('telefono', 20)->nullable();
             $table->string('direccion', 255)->nullable();
             $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PAS'])->nullable();
             $table->string('numero_documento', 20)->nullable();
        });
        
        Schema::table('mecanicos', function (Blueprint $table) {
             $table->string('telefono')->nullable();
             $table->string('direccion')->nullable();
             $table->enum('tipo_documento', ['DNI', 'RUC', 'CE', 'PAS'])->nullable();
             $table->string('numero_documento', 20)->nullable();
        });
    }
};
