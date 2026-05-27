<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanzaServicio;
use App\Models\OrdenServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanzaServicioController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'id_orden' => 'required|exists:ordenes_servicio,id',
            'concepto' => 'required|string|max:100',
            'tipo' => 'required|in:base,adicional',
            'monto' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $orden = OrdenServicio::findOrFail($request->id_orden);

            // Evitar modificaciones en órdenes ya finalizadas
            if ($orden->estado === 'finalizado') {
                return response()->json(['error' => 'No se pueden agregar costos a una orden finalizada'], 403);
            }

            $finanza = FinanzaServicio::create([
                'id_orden' => $request->id_orden,
                'concepto' => $request->concepto,
                'tipo' => $request->tipo,
                'monto' => $request->monto
            ]);

            // Recalcular el costo total de la orden usando la relación
            $nuevoTotal = $this->recalcularCostoTotalOrden($orden);

            DB::commit();

            return response()->json([
                'success' => true,
                'orden_costo_total_actualizado' => $nuevoTotal,
                'message' => 'Costo registrado correctamente',
                'data' => $finanza
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al registrar costo', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function getPorVehiculo(Request $request, $id_vehiculo)
    {
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

        $ordenId = $request->query('orden_id');
        $orden = $ordenId 
            ? $ordenes->firstWhere('id', (int)$ordenId) 
            : $ordenes->first();

        if (!$orden) {
            return response()->json(['message' => 'La orden solicitada no pertenece a este vehículo.'], 404);
        }
        
        $finanzas = FinanzaServicio::where('id_orden', $orden->id)->get();

        return response()->json([
            'finanzas' => $finanzas,
            'total' => $orden->costo_total,
            'ordenes' => $ordenes,
            'orden_seleccionada' => $orden->id
        ]);
    }

    /**
     * Recalcula y actualiza el costo total de una orden de servicio.
     */
    private function recalcularCostoTotalOrden(OrdenServicio $orden): float
    {
        $totalFinanzas = $orden->finanzas()->sum('monto');
        $orden->costo_total = $totalFinanzas;
        $orden->save();
        return $orden->costo_total;
    }
}
