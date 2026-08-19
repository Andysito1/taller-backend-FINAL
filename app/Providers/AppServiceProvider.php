<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensureStorageLinkExists();
    }

    /**
     * Railway (Nixpacks) puede reutilizar una capa de build cacheada y saltarse
     * `composer install` -> post-autoload-dump si composer.lock no cambió, por lo
     * que el hook que crea `public/storage` no siempre se ejecuta en cada deploy.
     * Como red de seguridad, verificamos y recreamos el symlink en cada arranque
     * de la aplicación: es una comprobación de filesystem muy barata.
     *
     * El destino usa PUBLIC_STORAGE_PATH (mismo valor que config/filesystems.php)
     * para poder apuntar exactamente a donde Railway monta el Volumen persistente,
     * en vez de asumir la ruta por defecto de Laravel.
     */
    private function ensureStorageLinkExists(): void
    {
        $link = public_path('storage');
        $target = env('PUBLIC_STORAGE_PATH', storage_path('app/public'));

        // Si ya es un symlink apuntando exactamente al destino correcto, no hay nada que hacer.
        if (is_link($link) && realpath(readlink($link)) === realpath($target)) {
            return;
        }

        // Si existe algo ahí (symlink viejo apuntando mal, o un archivo/carpeta suelta),
        // lo quitamos para poder recrear el symlink correcto.
        if (is_link($link) || file_exists($link)) {
            @unlink($link);
        }

        if (!is_dir($target)) {
            @mkdir($target, 0755, true);
        }

        if (!@symlink($target, $link)) {
            Log::warning("No se pudo crear el symlink de storage: $link -> $target");
        }
    }
}
