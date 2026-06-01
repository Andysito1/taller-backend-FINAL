<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ClientesServiciosExport; // Ahora funcionará tras mover el archivo
use App\Models\AuditoriaReporte;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function exportarClientesServicios(Request $request)
    {
        $request->validate([
            'tipo_filtro' => 'required|in:anio,mes_especifico,rango',
            'anio' => 'required|integer|min:2025',
            'mes' => 'nullable|integer|between:1,12',
            'mes_inicio' => 'nullable|integer|between:1,12',
            'mes_fin' => 'nullable|integer|between:1,12',
            'tipo_cliente' => 'nullable|string|in:persona,empresa',
            'servicios' => 'required|array',
            'servicios.*' => 'string'
        ]);

        $filtros = $request->all();
        
        // Constructor de query dinámico para validar existencia de datos
        $query = DB::table('ordenes_servicio as o')
            ->join('vehiculos as v', 'o.id_vehiculo', '=', 'v.id')
            ->join('clientes as c', 'v.id_cliente', '=', 'c.id')
            ->join('usuarios as u', 'c.id_usuario', '=', 'u.id')
            ->join('finanza_servicios as f', 'f.id_orden', '=', 'o.id')
            ->join('tipos_documento as td', 'u.id_tipo_documento', '=', 'td.id')
            ->whereYear('o.fecha_inicio', $request->anio)
            ->whereIn('o.titulo', $request->servicios);

        // Aplicar filtros de tiempo dinámicos
        if ($request->tipo_filtro === 'mes_especifico') {
            $query->whereMonth('o.fecha_inicio', $request->mes);
        } elseif ($request->tipo_filtro === 'rango') {
            $query->whereBetween(DB::raw('MONTH(o.fecha_inicio)'), [$request->mes_inicio, $request->mes_fin]);
        }

        // Filtro por tipo de cliente (Persona vs Empresa basado en tipo de documento)
        if ($request->tipo_cliente === 'persona') {
            $query->where('td.abreviatura', '!=', 'RUC');
        } elseif ($request->tipo_cliente === 'empresa') {
            $query->where('td.abreviatura', '=', 'RUC');
        }

        // Verificar si existen registros antes de procesar el Excel
        $totalRegistros = $query->count();

        if ($totalRegistros === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Sin registros para los filtros seleccionados'
            ], 404);
        }

        // Generar nombre de archivo único
        $fileName = 'reporte_clientes_servicios_' . now()->format('Ymd_His') . '.xlsx';

        AuditoriaReporte::create([
            'id_usuario' => Auth::id(),
            'tipo_reporte' => 'clientes_servicios',
            'filtros_aplicados' => $filtros,
            'formato' => 'xlsx',
            'total_registros' => $totalRegistros,
        ]);

        return Excel::download(new ClientesServiciosExport($filtros), $fileName);
    }
}