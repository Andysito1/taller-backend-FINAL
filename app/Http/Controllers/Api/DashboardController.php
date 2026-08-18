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
            ->with(['vehiculo.cliente.usuario', 'mecanico', 'servicio', 'etapas'])
            ->latest()
            ->get();

        // 'estado' en ordenes_servicio solo distingue en_proceso/pausado/finalizado.
        // Para un desglose útil (diagnóstico/reparación/pruebas/finalización) hay que
        // mirar la etapa realmente activa, igual que hace el panel de Órdenes.
        foreach ($ordenesDelMes as $orden) {
            $orden->etapa_actual = $this->etapaActual($orden);
        }

        $ingresosMes = (float) FinanzaServicio::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->sum('monto');

        $totalOrdenesMes = $ordenesDelMes->count();

        $finalizadasMes = OrdenServicio::whereYear('fecha_fin', $anio)
            ->whereMonth('fecha_fin', $mes)
            ->where('estado', 'finalizado')
            ->count();

        $ordenesActivasActual = OrdenServicio::whereNotIn('estado', ['finalizado', 'pausado'])->count();

        $porEstado = $ordenesDelMes->groupBy('etapa_actual')
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

    /**
     * Determina la etapa "visible" de una orden: diagnostico/reparacion/pruebas/
     * finalizacion mientras está activa, o finalizado/pausado si ya no lo está.
     * Replica la lógica getEtapaKey() usada en el panel de Órdenes del frontend.
     */
    private function etapaActual(OrdenServicio $orden): string
    {
        $estado = strtolower($orden->estado ?? '');

        if (in_array($estado, ['finalizado', 'pausado'])) {
            return $estado;
        }

        $etapaActiva = $orden->etapas->firstWhere('estado', 'en_proceso');
        if ($etapaActiva) {
            return $etapaActiva->etapa;
        }

        if (in_array($estado, ['diagnostico', 'reparacion', 'pruebas', 'finalizacion'])) {
            return $estado;
        }

        return 'diagnostico';
    }
}
