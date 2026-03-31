<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfiguracionUsuario;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfiguracionUsuarioController extends Controller
{
    /**
     * Obtiene la configuración del cliente autenticado.
     */
    public function show()
    {
        $user = Auth::user();
        $cliente = Cliente::where('id_usuario', $user->id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        // Busca la configuración o crea una por defecto si no existe
        $config = ConfiguracionUsuario::firstOrCreate(
            ['id_cliente' => $cliente->id],
            [
                'tema' => 'claro',
                'notificaciones_activas' => true
            ]
        );

        return response()->json($config);
    }

    /**
     * Crea o actualiza la configuración del cliente autenticado.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $cliente = Cliente::where('id_usuario', $user->id)->first();

        if (!$cliente) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        $validatedData = $request->validate([
            'tema' => ['required', 'string', Rule::in(['claro', 'oscuro'])],
            'notificaciones_activas' => 'required|boolean',
        ]);

        $configuracion = ConfiguracionUsuario::updateOrCreate(
            ['id_cliente' => $cliente->id],
            $validatedData
        );

        return response()->json($configuracion, 200);
    }
}
