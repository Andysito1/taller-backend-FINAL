<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanzaServicio;
use App\Models\OrdenServicio; // Asegúrate de importar esto
use Illuminate\Http\Request;

class FinanzaServicioController extends Controller
{
    // ... tu método store existente ...
    public function store(Request $request)
    {
        // ... (código existente) ...
        $request->validate([
            'id_orden' => 'required|exists:ordenes_servicio,id',
            'concepto' => 'required|string|max:100',
            'tipo' => 'required|in:base,adicional',
            'monto' => 'required|numeric|min:0',
        ]);

        $finanza = FinanzaServicio::create([
            'id_orden' => $request->id_orden,
            'concepto' => $request->concepto,
            'tipo' => $request->tipo,
            'monto' => $request->monto
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Costo registrado correctamente',
            'data' => $finanza
        ], 201);
    }

    // NUEVO MÉTODO: Obtener finanzas por vehículo
    public function getPorVehiculo(Request $request, $id_vehiculo)
{
    // 1. Obtenemos todas las órdenes asociadas al vehículo para el selector en la App
    $ordenes = OrdenServicio::where('id_vehiculo', $id_vehiculo)
                ->orderBy('created_at', 'desc')
                ->get();

    if ($ordenes->isEmpty()) {
        return response()->json([
            'finanzas' => [],
            'total' => 0.00,
            'ordenes' => []
        ]);
    }

    // 2. Determinamos qué orden visualizar basándonos en la selección del usuario o la más reciente
    $ordenId = $request->query('orden_id');
    
    $orden = $ordenId 
        ? $ordenes->firstWhere('id', (int)$ordenId) 
        : $ordenes->first(); // Por defecto la más reciente

    if (!$orden) {
        return response()->json(['message' => 'La orden solicitada no pertenece a este vehículo.'], 404);
    }

    // Obtenemos los costos asociados estrictamente a la orden encontrada
    $finanzas = FinanzaServicio::where('id_orden', $orden->id)->get();

    return response()->json([
        'finanzas' => $finanzas,
        'total' => $finanzas->sum('monto'),
        'ordenes' => $ordenes, // Enviamos todas las órdenes para el selector
        'orden_seleccionada' => $orden->id
    ]);
}

}
