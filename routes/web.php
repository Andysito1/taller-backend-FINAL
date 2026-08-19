<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Sirve archivos del disco 'public' sin depender del symlink public/storage,
// que no se está creando correctamente en este entorno de Railway.
Route::get('/storage/{path}', [\App\Http\Controllers\StorageFileController::class, 'show'])
    ->where('path', '.*');
