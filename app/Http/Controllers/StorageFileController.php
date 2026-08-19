<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class StorageFileController extends Controller
{
    /**
     * Sirve archivos del disco 'public' directamente desde Laravel, sin depender
     * del symlink public/storage -> storage/app/public. En este entorno de
     * Railway symlink() no está creando un enlace funcional (probablemente la
     * función está deshabilitada), así que las peticiones a /storage/... nunca
     * llegaban a un archivo estático y caían en el 404 por defecto del router.
     */
    public function show(string $path)
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404);
        }

        return $disk->response($path);
    }
}
