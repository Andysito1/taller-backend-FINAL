<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtapaServicio;
use App\Models\OrdenServicio;
use App\Events\NuevaNotificacion;
use App\Models\Notificacion;
use App\Models\ConfiguracionUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\FcmService;

class EtapaServicioController extends Controller
{
    protected $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,completado',
        ]);

        $etapa = EtapaServicio::findOrFail($id);
        $user = $request->user();
        $orden = $etapa->orden;

        // Asegurar que el rol esté cargado
        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        // Validar permisos: Solo ADMIN o el MECANICO asignado pueden actualizar
        if ($user->rol->nombre !== 'ADMIN' && (int)$orden->id_mecanico !== (int)$user->id) {
            return response()->json([
                'message' => 'No autorizado para actualizar esta etapa de servicio.'
            ], 403);
        }

        $etapa->estado = $request->estado;
        $etapa->save();

        // Lógica de transición de etapas
        // Si se completa una etapa y NO es diagnóstico, o si es diagnóstico pero ya está validado (caso raro)
        if ($request->estado === 'completado' && $etapa->etapa !== 'diagnostico') {
            $etapasOrden = $orden->etapas()->orderBy('id')->get();
            $currentIndex = $etapasOrden->search(fn($item) => $item->id === $etapa->id);

            if ($currentIndex !== false && isset($etapasOrden[$currentIndex + 1])) {
                $siguienteEtapa = $etapasOrden[$currentIndex + 1];
                if ($siguienteEtapa->estado === 'pendiente') {
                    $siguienteEtapa->estado = 'en_proceso';
                    $siguienteEtapa->save();

                    // Notificar también el inicio de la siguiente etapa
                    $this->crearYEnviarNotificacion(
                        $orden,
                        $siguienteEtapa,
                        'en_proceso'
                    );
                }
            }
        }

        // Notificación de la etapa actual (la que se acaba de actualizar)
        $this->crearYEnviarNotificacion($orden, $etapa, $request->estado);

        return response()->json([
            'success' => true,
            'message' => 'Estado de la etapa actualizado correctamente.',
            'data' => $etapa
        ]);
    }

    /**
     * Procesa la creación de notificación en BD, evento de socket y Push FCM.
     */
    private function crearYEnviarNotificacion($orden, $etapa, $estado)
    {
        // --- NOTIFICACIÓN (BD + FIREBASE) ---
        $orden->load('vehiculo.cliente.usuario');
        if ($orden->vehiculo && $orden->vehiculo->cliente) {
            $cliente = $orden->vehiculo->cliente;
            $idCliente = $cliente->id;

            // Nombres amigables
            $nombresEtapas = [
                'diagnostico' => 'Diagnóstico', 'reparacion' => 'Reparación', 
                'pruebas' => 'Pruebas de Calidad', 'finalizacion' => 'Finalización'
            ];
            $nombreEtapa = $nombresEtapas[$etapa->etapa] ?? ucfirst($etapa->etapa);
            $nuevoEstado = ucfirst(str_replace('_', ' ', $estado));
            $mensajeNotif = "La etapa '{$nombreEtapa}' ha sido actualizada a: {$nuevoEstado}";

            // 1. Guardar en BD
            $notificacion = Notificacion::create([
                'id_cliente' => $idCliente,
                'titulo'     => 'Avance de Servicio',
                'mensaje'    => $mensajeNotif,
                'tipo'       => 'servicio',
                'leido'      => false
            ]);

            // 2. Verificar configuración y emitir evento
            $config = ConfiguracionUsuario::where('id_cliente', $idCliente)->first();
            if (!$config || $config->notificaciones_activas) {
                $payload = $notificacion->toArray();
                if ($notificacion->created_at) {
                    $payload['created_at'] = $notificacion->created_at->toIso8601String();
                }
                event(new NuevaNotificacion($payload, 'cliente.' . $idCliente));

                // 3. ENVIAR PUSH POR FIREBASE (Si el usuario tiene token)
                if ($cliente->usuario && $cliente->usuario->fcm_token) {
                    $this->fcmService->enviarNotificacion(
                        $cliente->usuario->fcm_token,
                        'Avance de Vehículo: ' . ($orden->vehiculo->placa ?? ''),
                        $mensajeNotif,
                        [
                            'id'       => $notificacion->id,
                            'orden_id' => $orden->id,
                            'tipo'     => $notificacion->tipo,
                            'titulo'   => $notificacion->titulo,
                            'mensaje'  => $notificacion->mensaje,
                            'created_at' => $notificacion->created_at->toIso8601String(),
                            'leido'    => $notificacion->leido ? '1' : '0',
                            'estado'   => $etapa->estado
                        ]
                    );
                }
            }
        }
    }

    /**
     * Valida el diagnóstico de la orden de servicio.
     * Puede ser invocado por el Cliente (App Móvil) o por el ADMIN (Panel Web).
     */
    public function validarDiagnostico(Request $request, $idOrden)
    {
        // Validar que el estado enviado sea uno de los permitidos
        $request->validate([
            'estado' => 'required|in:aprobado,aclaracion'
        ]);

        $orden = OrdenServicio::with(['etapas', 'vehiculo.cliente'])->findOrFail($idOrden);
        $user = $request->user();
        $user->load('rol');

        // Validar que el usuario sea ADMIN o el CLIENTE dueño del vehículo
        $esAdmin = $user->rol->nombre === 'ADMIN';

        if (!$esAdmin) {
            // Buscamos el registro del cliente asociado al usuario autenticado
            $perfilCliente = \App\Models\Cliente::where('id_usuario', $user->id)->first();
            if (!$perfilCliente || (int)$orden->vehiculo->id_cliente !== (int)$perfilCliente->id) {
                return response()->json(['message' => 'No autorizado. Solo el dueño del vehículo o el administrador pueden validar el diagnóstico.'], 403);
            }
        }

        DB::beginTransaction();
        try {
            // 1. Actualizar el estado de validación (aprobado o aclaracion)
            $orden->validacion_diagnostico = $request->estado;
            $orden->save();

            // 2. Solo si se aprueba y el diagnóstico ya está completado, activar automáticamente la de reparación
            $etapaDiagnostico = $orden->etapas()->where('etapa', 'diagnostico')->first();

            if ($request->estado === 'aprobado') {
                // Al aprobar, el diagnóstico se da por finalizado automáticamente
                if ($etapaDiagnostico) {
                    $etapaDiagnostico->estado = 'completado';
                    $etapaDiagnostico->save();
                }

                // Se activa la etapa de reparación inmediatamente
                $etapaReparacion = $orden->etapas()->where('etapa', 'reparacion')->first();
                if ($etapaReparacion && $etapaReparacion->estado === 'pendiente') {
                    $etapaReparacion->estado = 'en_proceso';
                    $etapaReparacion->save();
                }
            }

            DB::commit();

            // Enviar notificación de que el proceso avanzó tras la validación
            $cliente = $orden->vehiculo->cliente;
            if ($cliente && $cliente->usuario) {
                $mensaje = $request->estado === 'aprobado' 
                    ? "¡Servicio aprobado! Tu vehículo ha pasado a la etapa de Reparación." 
                    : "Se ha solicitado una aclaración sobre el diagnóstico de tu vehículo.";

                $notificacion = Notificacion::create([
                    'id_cliente' => $cliente->id,
                    'titulo'     => 'Estado de Validación',
                    'mensaje'    => $mensaje,
                    'tipo'       => 'servicio',
                    'leido'      => false
                ]);

                // Emitir evento de Socket para actualización en tiempo real
                $config = ConfiguracionUsuario::where('id_cliente', $cliente->id)->first();
                if (!$config || $config->notificaciones_activas) {
                    $payload = $notificacion->toArray();
                    if ($notificacion->created_at) {
                        $payload['created_at'] = $notificacion->created_at->toIso8601String();
                    }
                    event(new NuevaNotificacion($payload, 'cliente.' . $cliente->id));
                }

                if ($cliente->usuario->fcm_token) {
                    $this->fcmService->enviarNotificacion(
                        $cliente->usuario->fcm_token,
                        'Actualización de Orden',
                        $mensaje,
                        [
                            'id'       => $notificacion->id,
                            'orden_id' => $orden->id,
                            'tipo'     => $notificacion->tipo,
                            'titulo'   => $notificacion->titulo,
                            'mensaje'  => $notificacion->mensaje,
                            'created_at' => $notificacion->created_at->toIso8601String(),
                            'leido'    => $notificacion->leido ? '1' : '0',
                            'estado'   => $request->estado === 'aprobado' ? 'en_proceso' : 'pendiente'
                        ]
                    );
                }
            }

            return response()->json([
                'success' => true,
                'message' => $request->estado === 'aprobado' ? 'Servicio aprobado.' : 'Solicitud de aclaración enviada.',
                'validacion' => $request->estado
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al validar diagnóstico'], 500);
        }
    }
}