<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtapaServicio;
use App\Models\EvidenciaServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenciaServicioController extends Controller
{
    /**
     * Lista las evidencias de una etapa. Accesible por ADMIN, el MECANICO asignado
     * a la orden y el CLIENTE dueño del vehículo (para verlas en el detalle de su servicio).
     */
    public function index(Request $request, $idEtapa)
    {
        $etapa = EtapaServicio::with('orden.vehiculo.cliente')->findOrFail($idEtapa);

        if (!$this->usuarioPuedeVer($request, $etapa)) {
            return response()->json(['message' => 'No autorizado para ver estas evidencias.'], 403);
        }

        return response()->json($etapa->evidencias()->latest()->get());
    }

    /**
     * Sube una evidencia (imagen o video) para una etapa. Solo el mecánico asignado
     * a la orden (o un ADMIN) puede hacerlo, y únicamente mientras esa etapa está
     * en curso (estado 'en_proceso') — no se permite adjuntar evidencia a etapas
     * pendientes o ya completadas.
     */
    public function store(Request $request, $idEtapa)
    {
        $etapa = EtapaServicio::with('orden')->findOrFail($idEtapa);
        $user = $request->user();

        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        $esAdmin = $user->rol->nombre === 'ADMIN';
        if (!$esAdmin && (int) $etapa->orden->id_mecanico !== (int) $user->id) {
            return response()->json(['message' => 'No autorizado para adjuntar evidencias en esta orden.'], 403);
        }

        if ($etapa->estado !== 'en_proceso') {
            return response()->json(['message' => 'Solo se pueden adjuntar evidencias mientras la etapa está en curso.'], 422);
        }

        $request->validate([
            'archivo' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $archivo = $request->file('archivo');
        $esVideo = in_array(strtolower($archivo->getClientOriginalExtension()), ['mp4', 'mov', 'avi']);

        $path = $archivo->store('evidencias', 'public');

        $evidencia = EvidenciaServicio::create([
            'id_etapa' => $etapa->id,
            'tipo' => $esVideo ? 'video' : 'imagen',
            'archivo_url' => $path,
            'descripcion' => $request->descripcion,
        ]);

        return response()->json(['message' => 'Evidencia registrada correctamente', 'data' => $evidencia], 201);
    }

    public function destroy(Request $request, $id)
    {
        $evidencia = EvidenciaServicio::with('etapa.orden')->findOrFail($id);
        $user = $request->user();

        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        $esAdmin = $user->rol->nombre === 'ADMIN';
        if (!$esAdmin && (int) $evidencia->etapa->orden->id_mecanico !== (int) $user->id) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if ($evidencia->archivo_url) {
            Storage::disk('public')->delete($evidencia->archivo_url);
        }
        $evidencia->delete();

        return response()->json(['message' => 'Evidencia eliminada correctamente']);
    }

    private function usuarioPuedeVer(Request $request, EtapaServicio $etapa): bool
    {
        $user = $request->user();
        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        if ($user->rol->nombre === 'ADMIN') {
            return true;
        }

        if ($user->rol->nombre === 'MECANICO') {
            return (int) $etapa->orden->id_mecanico === (int) $user->id;
        }

        if ($user->rol->nombre === 'CLIENTE') {
            $cliente = \App\Models\Cliente::where('id_usuario', $user->id)->first();
            return $cliente && $etapa->orden->vehiculo && (int) $etapa->orden->vehiculo->id_cliente === (int) $cliente->id;
        }

        return false;
    }
}
