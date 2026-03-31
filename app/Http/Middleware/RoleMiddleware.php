<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user()->load('rol');

        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Cargar el rol si no está cargado
        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        if (!$user->rol) {
            return response()->json(['error' => 'Usuario sin rol asignado'], 403);
        }

        // Verificar si el rol del usuario está en los roles permitidos
        if (!in_array($user->rol->nombre, $roles)) {
            return response()->json([
                'error' => 'No autorizado',
                'required_roles' => $roles,
                'user_role' => $user->rol->nombre
            ], 403);
        }

        return $next($request);
    }
}
