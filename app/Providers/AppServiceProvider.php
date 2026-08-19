<?php

namespace App\Providers;

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
     */
    private function ensureStorageLinkExists(): void
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        if (is_link($link) || file_exists($link)) {
            return;
        }

        if (!is_dir($target)) {
            @mkdir($target, 0755, true);
        }

        @symlink($target, $link);
    }
}
