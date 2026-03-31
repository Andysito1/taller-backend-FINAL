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
        // 1. Crear la tabla maestra de tipos de documento
        Schema::create('tipos_documento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50);
            $table->string('abreviatura', 10)->unique();
            $table->integer('longitud_exacta')->nullable();
            $table->integer('longitud_maxima')->nullable();
            $table->timestamps();
        });

        // 2. Insertar datos iniciales
        DB::table('tipos_documento')->insert([
            ['nombre' => 'DNI', 'abreviatura' => 'DNI', 'longitud_exacta' => 8, 'longitud_maxima' => 8],
            ['nombre' => 'RUC', 'abreviatura' => 'RUC', 'longitud_exacta' => 11, 'longitud_maxima' => 11],
            ['nombre' => 'Carnet de Extranjería', 'abreviatura' => 'CE', 'longitud_exacta' => null, 'longitud_maxima' => 12],
            ['nombre' => 'Pasaporte', 'abreviatura' => 'PAS', 'longitud_exacta' => null, 'longitud_maxima' => 12],
        ]);

        // 3. Agregar FK a la tabla usuarios
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('id_tipo_documento')->nullable()->after('id_rol')->constrained('tipos_documento');
        });

        // 4. Migrar datos existentes de string a ID
        $tipos = DB::table('tipos_documento')->get();
        foreach ($tipos as $tipo) {
            DB::table('usuarios')
                ->where('tipo_documento', $tipo->abreviatura)
                ->update(['id_tipo_documento' => $tipo->id]);
        }

        // 5. Eliminar la columna antigua
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('tipo_documento');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('tipo_documento', 10)->nullable();
        });
        Schema::dropIfExists('tipos_documento');
    }
};