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
        Schema::create('evidencias_servicio', function (Blueprint $table) {
    $table->id();
    $table->foreignId('id_etapa')->constrained('etapas_servicio')->onDelete('cascade');
    $table->enum('tipo', ['imagen', 'video']);
    $table->string('archivo_url', 500);
    $table->string('descripcion', 255)->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
