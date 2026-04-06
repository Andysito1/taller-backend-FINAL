<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use Illuminate\Http\Request;

class SeguimientoController extends Controller
{
    public function show($id_vehiculo)
    {
        // 1. Verificar existencia del vehículo
        $vehiculo = Vehiculo::find($id_vehiculo);
        if (!$vehiculo) {
            return response()->json(['message' => 'Vehículo no encontrado'], 404);
        }

        // 2. Buscar la orden más reciente para este vehículo.
        // Usamos 'latest()' para obtener la última creada.
        // Si quisieras filtrar solo las activas, podrías agregar ->where('estado', 'en_proceso')
        $orden = OrdenServicio::where('id_vehiculo', $id_vehiculo)
                    ->with('etapas') // Cargar la relación de etapas
                    ->latest('id') // Aseguramos que sea por el ID más alto (el más reciente)
                    ->first();

        // Si no hay orden, retornamos lista vacía (la app mostrará "No hay orden activa")
        if (!$orden) {
            return response()->json(['id' => null, 'id_orden' => null, 'etapas' => []]);
        }

        // 3. Formatear las etapas para la App Móvil
        // Transformamos los datos de la BD (ej: 'diagnostico', 'en_proceso') 
        // a textos legibles para el usuario (ej: 'Diagnóstico', 'En Progreso')
        $etapas = $orden->etapas->map(function($etapa) {
            return [
                'id' => $etapa->id,
                'titulo' => $this->getTituloEtapa($etapa->etapa),
                'descripcion' => $this->getDescripcionEtapa($etapa->etapa),
                'estado' => $this->formatEstado($etapa->estado),
                'fecha' => $etapa->updated_at ? $etapa->updated_at->format('d M, Y - H:i') : null,
                'tipo' => strtoupper($etapa->etapa), // DIAGNOSTICO, REPARACION, PRUEBAS, FINALIZACION
            ];
        });

        return response()->json([
            'id' => $orden->id, // Agregamos 'id' para que el modelo del móvil lo reconozca automáticamente
            'id_orden' => $orden->id,
            'etapas' => $etapas,
            'validacion' => [
                'estado' => $orden->validacion_diagnostico,
                'mensaje_cliente' => $orden->validacion_diagnostico === 'aclaracion' 
                    ? 'El taller se pondrá en contacto contigo' 
                    : null
            ]
        ]);
    }

    // --- Helpers para textos visuales ---

    private function getTituloEtapa($tipo) {
        return match($tipo) {
            'diagnostico' => 'Diagnóstico',
            'reparacion' => 'Reparación',
            'pruebas' => 'Pruebas de Calidad',
            'finalizacion' => 'Finalización y Entrega',
            default => ucfirst($tipo),
        };
    }

    private function getDescripcionEtapa($tipo) {
        return match($tipo) {
            'diagnostico' => 'Inspección inicial y detección de fallas.',
            'reparacion' => 'Ejecución de los trabajos mecánicos necesarios.',
            'pruebas' => 'Verificación del funcionamiento correcto del vehículo.',
            'finalizacion' => 'Lavado, revisión final y preparación para entrega.',
            default => 'Etapa del servicio.',
        };
    }

    private function formatEstado($estado) {
        // Mapeo exacto para coincidir con el switch de colores en Flutter
        return match($estado) {
            'pendiente' => 'Pendiente',
            'en_proceso' => 'En Progreso',
            'completado' => 'Completado',
            default => 'Pendiente',
        };
    }
}
