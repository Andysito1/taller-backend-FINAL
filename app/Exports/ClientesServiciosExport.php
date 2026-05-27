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

class ClientesServiciosExport implements FromQuery, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    use Exportable;

    public function __construct(protected array $filtros) {}

    public function query()
    {
        $query = OrdenServicio::query()
            ->leftJoin('vehiculos', 'ordenes_servicio.id_vehiculo', '=', 'vehiculos.id')
            ->leftJoin('clientes', 'vehiculos.id_cliente', '=', 'clientes.id')
            ->leftJoin('usuarios', 'clientes.id_usuario', '=', 'usuarios.id')
            ->leftJoin('servicios', 'ordenes_servicio.id_servicio', '=', 'servicios.id')
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