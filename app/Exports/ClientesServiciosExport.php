<?php

namespace App\Exports;

use App\Models\OrdenServicio;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;

class ClientesServiciosExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    use Exportable;

    public function __construct(protected array $filtros) {}

    public function query()
    {
        $query = OrdenServicio::query()
            ->join('vehiculos', 'ordenes_servicio.id_vehiculo', '=', 'vehiculos.id')
            ->join('clientes', 'vehiculos.id_cliente', '=', 'clientes.id')
            ->join('usuarios', 'clientes.id_usuario', '=', 'usuarios.id')
            ->join('tipos_documento', 'usuarios.id_tipo_documento', '=', 'tipos_documento.id')
            ->join('servicios', 'ordenes_servicio.id_servicio', '=', 'servicios.id')
            ->select([
                'usuarios.nombre as cliente_nombre',
                'usuarios.correo as cliente_correo',
                'usuarios.telefono as cliente_telefono',
                'vehiculos.placa',
                'vehiculos.marca',
                'vehiculos.modelo',
                'servicios.nombre as servicio_nombre',
                'ordenes_servicio.costo_total',
                'ordenes_servicio.fecha_inicio',
                'ordenes_servicio.estado'
            ]);

        // 1. Filtro por Año (Siempre viene según la validación)
        $query->whereYear('ordenes_servicio.fecha_inicio', $this->filtros['anio']);

        // 2. Filtro de Tiempo Dinámico
        if ($this->filtros['tipo_filtro'] === 'mes_especifico' && !empty($this->filtros['mes'])) {
            $query->whereMonth('ordenes_servicio.fecha_inicio', $this->filtros['mes']);
        } elseif ($this->filtros['tipo_filtro'] === 'rango' && !empty($this->filtros['mes_inicio']) && !empty($this->filtros['mes_fin'])) {
            $query->whereBetween(DB::raw('MONTH(ordenes_servicio.fecha_inicio)'), [
                $this->filtros['mes_inicio'],
                $this->filtros['mes_fin']
            ]);
        }

        // 3. Filtro por Tipo de Cliente (Persona vs Empresa)
        if (!empty($this->filtros['tipo_cliente'])) {
            if ($this->filtros['tipo_cliente'] === 'persona') {
                $query->where('tipos_documento.abreviatura', '!=', 'RUC');
            } elseif ($this->filtros['tipo_cliente'] === 'empresa') {
                $query->where('tipos_documento.abreviatura', '=', 'RUC');
            }
        }

        // 4. Filtro por Servicios Seleccionados (Array)
        if (!empty($this->filtros['servicios'])) {
            $query->whereIn('servicios.nombre', $this->filtros['servicios']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Nombre del Cliente',
            'Correo',
            'Teléfono',
            'Placa Vehículo',
            'Marca',
            'Modelo',
            'Servicio Adquirido',
            'Costo Total',
            'Fecha de Servicio',
            'Estado'
        ];
    }

    public function map($orden): array
    {
        return [
            $orden->cliente_nombre,
            $orden->cliente_correo,
            $orden->cliente_telefono,
            $orden->placa,
            $orden->marca,
            $orden->modelo,
            $orden->servicio_nombre ?? 'Sin servicio asignado',
            'S/ ' . number_format($orden->costo_total, 2),
            $orden->fecha_inicio ? \Carbon\Carbon::parse($orden->fecha_inicio)->format('d/m/Y') : 'N/A',
            ucfirst($orden->estado)
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $headerRange = 'A1:J1';

                $event->sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => Color::COLOR_WHITE],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => Color::COLOR_BLACK],
                    ],
                ]);
                
                // Bordes para los encabezados
                $event->sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Habilitar filtros
                $event->sheet->getDelegate()->setAutoFilter($headerRange);

                // Congelar la fila superior para que los encabezados siempre sean visibles
                $event->sheet->getDelegate()->freezePane('A2');
            },
        ];
    }
}