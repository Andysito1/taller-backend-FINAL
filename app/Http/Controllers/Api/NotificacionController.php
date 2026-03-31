<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmService;

class NotificacionController extends Controller
{
    /**
     * Listar notificaciones del cliente autenticado
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Buscar el perfil de cliente asociado al usuario
        $cliente = Cliente::where('id_usuario', $user->id)->first();

        if (!$cliente) {
            return response()->json([]);
        }

        try {
            $notificaciones = Notificacion::where('id_cliente', $cliente->id)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();

            return response()->json($notificaciones);
        } catch (\Exception $e) {
            Log::error("Error en NotificacionController@index: " . $e->getMessage());
            return response()->json(['error' => 'Error al cargar notificaciones', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * Marcar una notificación como leída
     */
    public function marcarLeida($id)
    {
        $notificacion = Notificacion::findOrFail($id);
        
        // Validar seguridad: que la notificación pertenezca al usuario actual
        $user = Auth::user();
        $cliente = Cliente::where('id_usuario', $user->id)->first();

        if (!$cliente || $notificacion->id_cliente !== $cliente->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $notificacion->leido = true;
        $notificacion->save();

        return response()->json(['success' => true]);
    }
    
    /**
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas()
    {
        $user = Auth::user();
        $cliente = Cliente::where('id_usuario', $user->id)->first();

        if ($cliente) {
            Notificacion::where('id_cliente', $cliente->id)
                ->where('leido', false)
                ->update(['leido' => true]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Crear una nueva notificación (Manual/Admin) y enviarla vía Firebase
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_cliente' => 'required|exists:clientes,id',
            'titulo'     => 'required|string|max:100',
            'mensaje'    => 'required|string',
            'tipo'       => 'nullable|string'
        ]);

        try {
            // 1. Persistir en Base de Datos
            $notificacion = Notificacion::create([
                'id_cliente' => $request->id_cliente,
                'titulo'     => $request->titulo,
                'mensaje'    => $request->mensaje,
                'tipo'       => $request->tipo ?? 'general',
                'leido'      => false
            ]);

            // 2. Enviar Push via Firebase si el cliente tiene token
            $cliente = Cliente::with('usuario')->find($request->id_cliente);
            
            if ($cliente && $cliente->usuario && $cliente->usuario->fcm_token) {
                $fcmService = app(FcmService::class);
                $fcmService->enviarNotificacion(
                    $cliente->usuario->fcm_token,
                    $notificacion->titulo,
                    $notificacion->mensaje,
                    [
                        'id'   => $notificacion->id,
                        'tipo' => $notificacion->tipo,
                        'titulo' => $notificacion->titulo,
                        'mensaje' => $notificacion->mensaje,
                        'created_at' => $notificacion->created_at->toIso8601String(),
                        'leido' => $notificacion->leido ? '1' : '0'
                    ]
                );
            }

            return response()->json($notificacion, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar notificación', 'detalle' => $e->getMessage()], 500);
        }
    }
}