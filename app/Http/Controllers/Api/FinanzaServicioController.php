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
    public function getPorVehiculo($id_vehiculo)
    {
        // Buscamos la última orden generada para este vehículo
        $orden = OrdenServicio::where('id_vehiculo', $id_vehiculo)
                    ->latest()
                    ->first();

        if (!$orden) {
            return response()->json([
                'finanzas' => [],
                'total' => 0.00
            ]);
        }

        // Obtenemos los costos asociados a esa orden
        $finanzas = FinanzaServicio::where('id_orden', $orden->id)->get();

        return response()->json([
            'finanzas' => $finanzas,
            'total' => $finanzas->sum('monto') // Laravel calcula la suma automáticamente
        ]);
    }
}
