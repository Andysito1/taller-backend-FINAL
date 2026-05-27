<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ClientesServiciosExport; // Ahora funcionará tras mover el archivo
use App\Models\AuditoriaReporte;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    public function exportarClientesServicios(Request $request)
    {
        $request->validate([
            'tipo_filtro' => 'required|in:anio,mes_especifico,rango',
            'anio' => 'nullable|integer|min:2000',
            'mes' => 'nullable|integer|between:1,12',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $filtros = [];
        
        if ($request->tipo_filtro === 'anio') {
            $filtros['anio'] = $request->anio ?? Carbon::now()->year;
        } elseif ($request->tipo_filtro === 'mes_especifico') {
            $request->validate(['mes' => 'required|integer|between:1,12']);
            $filtros['anio'] = $request->anio ?? Carbon::now()->year;
            $filtros['mes'] = $request->mes;
        } else {
            $filtros['fecha_inicio'] = $request->fecha_inicio;
            $filtros['fecha_fin'] = $request->fecha_fin;
        }

        // Generar nombre de archivo único
        $fileName = 'reporte_clientes_servicios_' . now()->format('Ymd_His') . '.xlsx';

        // Auditoría del reporte (usando tu modelo AuditoriaReporte)
        $export = new ClientesServiciosExport($filtros);
        
        // Contar registros antes de exportar para la auditoría
        $totalRegistros = $export->query()->count();

        AuditoriaReporte::create([
            'id_usuario' => Auth::id(),
            'tipo_reporte' => 'clientes_servicios',
            'filtros_aplicados' => $filtros,
            'formato' => 'xlsx',
            'total_registros' => $totalRegistros,
        ]);

        return Excel::download($export, $fileName);
    }
}