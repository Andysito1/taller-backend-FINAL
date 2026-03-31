<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenServicio;
use App\Models\EtapaServicio;
use App\Models\Vehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrdenServicioController extends Controller
{
    /**
     * Listar todas las órdenes (Vista Admin)
     */
    public function index()
    {
        $ordenes = OrdenServicio::with(['vehiculo.cliente.usuario', 'mecanico', 'etapas'])
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
            'titulo'       => 'required|string|max:100',
            'descripcion'  => 'required|string',
            'fecha_inicio' => 'required|date',
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
                'titulo'       => $request->titulo,
                'descripcion'  => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'estado'       => 'en_proceso',
                'validacion_diagnostico' => 'en_espera'
            ]);

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
        $orden = OrdenServicio::with(['vehiculo.cliente.usuario', 'mecanico', 'etapas'])
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
            ->with(['vehiculo.cliente.usuario', 'etapas'])
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
            ->with('etapas')
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
            ->with(['etapas', 'mecanico'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($historial);
    }
}