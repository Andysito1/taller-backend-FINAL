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

        // Aplicar filtros de tiempo
        if (!empty($this->filtros['fecha_inicio']) && !empty($this->filtros['fecha_fin'])) {
            $query->whereBetween('ordenes_servicio.fecha_inicio', [$this->filtros['fecha_inicio'], $this->filtros['fecha_fin']]);
        } elseif (!empty($this->filtros['anio'])) {
            $query->whereYear('ordenes_servicio.fecha_inicio', $this->filtros['anio']);
            if (!empty($this->filtros['mes'])) {
                $query->whereMonth('ordenes_servicio.fecha_inicio', $this->filtros['mes']);
            }
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
            $orden->servicio_nombre,
            'S/ ' . number_format($orden->costo_total, 2),
            $orden->fecha_inicio,
            ucfirst($orden->estado)
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Aplicar el FILTRO solicitado para organizar por Servicio o cualquier columna.
                // Asumiendo que los encabezados están en la fila 1 y van de la columna A a la J.
                $headerRange = 'A1:J1';

                $event->sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => Color::COLOR_WHITE], // Texto blanco
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => Color::COLOR_BLACK], // Fondo negro
                    ],
                ]);
                $event->sheet->getDelegate()->setAutoFilter('A1:J1');
            },
        ];
    }
}