<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        // Insertar servicios requeridos
        DB::table('servicios')->insert([
            ['nombre' => 'Traccionamiento', 'created_at' => now()],
            ['nombre' => 'Planchado', 'created_at' => now()],
            ['nombre' => 'Pintura', 'created_at' => now()],
            ['nombre' => 'Mantenimiento', 'created_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};