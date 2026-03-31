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
        Schema::table('clientes', function (Blueprint $table) {
            $table->enum('tipo_documento', ['DNI', 'RUC'])
                ->after('id_usuario');

            $table->string('numero_documento', 11)
                ->unique()
                ->after('tipo_documento');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['tipo_documento', 'numero_documento']);
        });
    }
};
