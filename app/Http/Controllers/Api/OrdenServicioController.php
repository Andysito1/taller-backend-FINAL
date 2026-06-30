<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderShipped;
use App\Models\OrdenServicio;
use App\Models\EtapaServicio;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use App\Models\Notificacion;
use App\Models\ConfiguracionUsuario;
use App\Services\BrevoMailer;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrdenServicioController extends Controller
{
    /**
     * Inyectar servicio de notificaciones Push y Brevo
     */
    public function __construct(protected FcmService $fcmService, protected BrevoMailer $brevoMailer)
    {
    }

    /**
     * Listar todas las órdenes (Vista Admin)
     */
    public function index()
    {
        $ordenes = OrdenServicio::with(['vehiculo.cliente.usuario', 'mecanico', 'etapas', 'servicio'])
            ->latest()
            ->get();
        return response()->json($ordenes);
    }

    /**
     * Crear una nueva orden y sus etapas iniciales
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_vehiculo'  => 'required|exists:vehiculos,id',
            'id_mecanico'  => 'required|exists:usuarios,id',
            'id_servicio'  => 'required|exists:servicios,id', // Nuevo campo
            'titulo'       => 'required|string|max:100',
            'descripcion'  => 'required|string',
            'fecha_inicio' => 'required|date',
            'costo_total'  => 'nullable|numeric|min:0', // Nuevo campo, puede ser nulo al inicio
        ]);

        // Verificar si el vehículo ya tiene una orden activa
        $ordenActiva = OrdenServicio::where('id_vehiculo', $request->id_vehiculo)
            ->where('estado', 'en_proceso')
            ->exists();

        if ($ordenActiva) {
            return response()->json(['error' => 'El vehículo ya tiene una orden de servicio en curso.'], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Crear la Orden
            $orden = OrdenServicio::create([
                'id_vehiculo'  => $request->id_vehiculo,
                'id_mecanico'  => $request->id_mecanico,
                'id_servicio'  => $request->id_servicio, // Asignar el servicio
                'titulo'       => $request->titulo,
                'descripcion'  => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'estado'       => 'en_proceso',
                'costo_total'  => $request->costo_total ?? 0.00, // Establecer costo inicial
                'validacion_diagnostico' => 'en_espera'
            ]);

            // Registro del costo base inicial en la tabla de finanzas para consistencia
            if ($orden->costo_total > 0) {
                \App\Models\FinanzaServicio::create([
                    'id_orden' => $orden->id,
                    'concepto' => 'Costo Base de Servicio',
                    'tipo' => 'base',
                    'monto' => $orden->costo_total
                ]);
            }

            // 2. Crear las 4 etapas obligatorias para el seguimiento
            $etapas = ['diagnostico', 'reparacion', 'pruebas', 'finalizacion'];
            
            foreach ($etapas as $index => $nombreEtapa) {
                EtapaServicio::create([
                    'id_orden' => $orden->id,
                    'etapa'    => $nombreEtapa,
                    'estado'   => ($index === 0) ? 'en_proceso' : 'pendiente' // La primera empieza en proceso
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Orden de servicio creada con éxito', 'data' => $orden], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'No se pudo crear la orden', 'detalle' => $e->getMessage()], 500);
        }
    }

    /**
     * Ver detalle de una orden específica
     */
    public function show($id)
    {
        $orden = OrdenServicio::with(['vehiculo.cliente.usuario', 'mecanico', 'etapas', 'servicio'])
            ->findOrFail($id);
        return response()->json($orden);
    }

    /**
     * Órdenes asignadas al mecánico autenticado
     */
    public function misOrdenes()
    {
        $user = Auth::user();
        $ordenes = OrdenServicio::where('id_mecanico', $user->id)
            ->with(['vehiculo.cliente.usuario', 'etapas', 'servicio'])
            ->latest()
            ->get();
        return response()->json($ordenes);
    }

    /**
     * Obtener seguimiento de un vehículo (Vista Cliente)
     */
    public function seguimientoVehiculo($id_vehiculo)
    {
        $orden = OrdenServicio::where('id_vehiculo', $id_vehiculo)
            ->with(['etapas', 'servicio'])
            ->latest()
            ->first();

        if (!$orden) {
            return response()->json(['message' => 'No hay órdenes activas para este vehículo'], 404);
        }

        return response()->json($orden);
    }

    /**
     * Historial de órdenes por vehículo
     */
    public function historialPorVehiculo($id_vehiculo)
    {
        $historial = OrdenServicio::where('id_vehiculo', $id_vehiculo)
            ->where('estado', 'finalizado')
            ->with(['etapas', 'mecanico', 'servicio'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($historial);
    }

    /**
     * Actualizar una orden (General)
     */
    public function update(Request $request, $id)
    {
        $orden = OrdenServicio::findOrFail($id);

        $request->validate([
            'estado' => 'sometimes|in:en_proceso,pausado,finalizado',
            'fecha_fin' => 'nullable|date',
            'id_servicio' => 'sometimes|exists:servicios,id', // Permitir actualizar el servicio
            'costo_total' => 'sometimes|numeric|min:0', // Permitir actualizar el costo total
        ]);

        $data = $request->only(['estado', 'fecha_fin', 'titulo', 'descripcion', 'id_servicio', 'costo_total']);

        // Si el estado cambia a finalizado, asegurar fecha_fin
        if (isset($data['estado']) && $data['estado'] === 'finalizado' && !$orden->fecha_fin) {
            $data['fecha_fin'] = Carbon::now()->toDateString();
        }

        $estadoAnterior = $orden->getOriginal('estado');
        $orden->update($data);

        // Si el estado cambió, notificar al cliente
        if (isset($data['estado']) && $data['estado'] !== $estadoAnterior) {
            $this->notificarCambioEstadoOrden($orden);
        }

        return response()->json(['message' => 'Orden actualizada con éxito', 'data' => $orden]);
    }

    /**
     * Lógica de notificación cuando cambia el estado general de la orden
     */
    public function notificarAutoListo(Request $request, $id)
    {
        $vehiculo = Vehiculo::with(['cliente.usuario', 'ordenes' => function ($query) {
            $query->latest();
        }])->findOrFail($id);

        $usuario = $vehiculo->cliente?->usuario;

        if (!$usuario || !$usuario->correo) {
            return response()->json([
                'message' => 'El propietario del vehiculo no tiene un correo registrado.',
            ], 422);
        }

        $orden = $vehiculo->ordenes->firstWhere('estado', 'finalizado') ?? $vehiculo->ordenes->first();

        if (!$orden) {
            return response()->json([
                'message' => 'El vehiculo no tiene una orden de servicio disponible para notificar.',
            ], 422);
        }

        if ($orden->estado !== 'finalizado') {
            $orden->estado = 'finalizado';
            $orden->fecha_fin = $orden->fecha_fin ?? Carbon::now()->toDateString();
            $orden->save();
        }

        try {
            $this->brevoMailer->sendMailable($usuario->correo, new OrderShipped($orden));

            return response()->json([
                'message' => 'Notificacion de auto listo enviada correctamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar la notificacion de auto listo por SMTP.', [
                'vehiculo_id' => $vehiculo->id,
                'orden_id' => $orden->id,
                'correo' => $usuario->correo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo enviar la notificacion de auto listo.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function notificarServicioCompletado($id)
    {
        $orden = OrdenServicio::with(['vehiculo.cliente.usuario'])->findOrFail($id);

        if ($orden->estado !== 'finalizado') {
            return response()->json([
                'message' => 'Solo se pueden enviar correos para órdenes ya finalizadas.',
            ], 422);
        }

        $usuario = $orden->vehiculo?->cliente?->usuario;

        if (!$usuario || !$usuario->correo) {
            return response()->json([
                'message' => 'El propietario del vehículo no tiene un correo registrado.',
            ], 422);
        }

        try {
            $this->brevoMailer->sendMailable($usuario->correo, new OrderShipped($orden));

            return response()->json([
                'message' => 'Correo de servicio completado enviado correctamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de servicio completado.', [
                'orden_id' => $orden->id,
                'correo' => $usuario->correo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo enviar el correo de servicio completado.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    private function notificarCambioEstadoOrden($orden)
    {
        // 2. Enviar correo usando el mailer configurado en .env
        // Asegúrate de que el Mailable OrderShipped ahora usa OrdenServicio
        if ($orden->estado === 'finalizado' && $orden->vehiculo && $orden->vehiculo->cliente && $orden->vehiculo->cliente->usuario && $orden->vehiculo->cliente->usuario->correo) {
            try {
                $this->brevoMailer->sendMailable($orden->vehiculo->cliente->usuario->correo, new OrderShipped($orden));
            } catch (\Exception $e) {
                Log::error("Error al enviar email de actualizacion de orden por Brevo: " . $e->getMessage());
            }
        }

        // Lógica de notificación Push existente
        $orden->load('vehiculo.cliente.usuario');
        if ($orden->vehiculo && $orden->vehiculo->cliente) {
            $cliente = $orden->vehiculo->cliente;
            $nuevoEstado = ucfirst(str_replace('_', ' ', $orden->estado));
            
            $mensaje = $orden->estado === 'finalizado' 
                ? "¡Tu vehículo con placa {$orden->vehiculo->placa} ya está listo!" 
                : "El estado de tu servicio ha cambiado a: {$nuevoEstado}";

            // 1. Guardar en BD
            $notificacion = Notificacion::create([
                'id_cliente' => $cliente->id,
                'titulo'     => 'Estado de Servicio',
                'mensaje'    => $mensaje,
                'tipo'       => 'servicio',
                'leido'      => false
            ]);

            // Verificar configuración y enviar Push
            $config = ConfiguracionUsuario::where('id_cliente', $cliente->id)->first();
            if (!$config || $config->notificaciones_activas) {
                if ($cliente->usuario && $cliente->usuario->fcm_token) {
                    try {
                        $this->fcmService->enviarNotificacion(
                            $cliente->usuario->fcm_token,
                            'Taller: Actualización de Orden',
                            $mensaje,
                            [
                                'id' => $notificacion->id,
                                'orden_id' => $orden->id,
                                'tipo' => 'servicio'
                            ]
                        );
                    } catch (\Exception $e) {
                        Log::warning("Error FCM en OrdenServicio: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
