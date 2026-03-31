<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'nombre' => 'Andy Sullcaray',
            'correo' => 'i2414593@continental.edu.pe',
            'password' => Hash::make('60905577'),
            'id_rol' => 1, // ADMIN
            'activo' => 1
        ]);
    }
}
