<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenServicio;
use App\Models\FinanzaServicio;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Resumen del panel de control para un mes/año dado (por defecto, el mes actual).
     */
    public function resumen(Request $request)
    {
        $anio = (int) $request->query('anio', now()->year);
        $mes = (int) $request->query('mes', now()->month);

        $ordenesDelMes = OrdenServicio::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->with(['vehiculo.cliente.usuario', 'mecanico', 'servicio'])
            ->latest()
            ->get();

        $ingresosMes = (float) FinanzaServicio::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->sum('monto');

        $totalOrdenesMes = $ordenesDelMes->count();

        $finalizadasMes = OrdenServicio::whereYear('fecha_fin', $anio)
            ->whereMonth('fecha_fin', $mes)
            ->where('estado', 'finalizado')
            ->count();

        $ordenesActivasActual = OrdenServicio::whereNotIn('estado', ['finalizado', 'pausado'])->count();

        $porEstado = $ordenesDelMes->groupBy('estado')
            ->map(fn($grupo) => $grupo->count());

        $porMecanico = $ordenesDelMes->groupBy(fn($o) => $o->mecanico->nombre ?? 'Sin asignar')
            ->map(fn($grupo) => $grupo->count())
            ->sortDesc()
            ->take(5);

        $porServicio = $ordenesDelMes->groupBy(fn($o) => $o->servicio->nombre ?? 'N/A')
            ->map(fn($grupo) => $grupo->count())
            ->sortDesc();

        return response()->json([
            'anio' => $anio,
            'mes' => $mes,
            'total_ordenes_mes' => $totalOrdenesMes,
            'ordenes_finalizadas_mes' => $finalizadasMes,
            'ordenes_activas_actual' => $ordenesActivasActual,
            'ingresos_mes' => $ingresosMes,
            'ingreso_promedio_orden' => $totalOrdenesMes > 0 ? round($ingresosMes / $totalOrdenesMes, 2) : 0,
            'por_estado' => $porEstado,
            'por_mecanico' => $porMecanico,
            'por_servicio' => $porServicio,
            'ordenes' => $ordenesDelMes->values(),
        ]);
    }
}
