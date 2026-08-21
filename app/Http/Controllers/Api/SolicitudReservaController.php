<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SolicitudReserva;
use App\Models\Usuario;
use App\Rules\NumeroDocumentoValido;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SolicitudReservaController extends Controller
{
    /**
     * Crear una solicitud de reserva desde el formulario público de la home.
     * No requiere autenticación.
     */
    public function store(Request $request)
    {
        $anioActual = (int) now()->format('Y');

        $data = $request->validate([
            'id_tipo_documento' => 'required|exists:tipos_documento,id',
            'numero_documento' => ['required', 'string', new NumeroDocumentoValido($request->integer('id_tipo_documento'))],
            'correo' => ['required', 'email', 'regex:/^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/'],
            'telefono' => 'required|string|regex:/^[0-9]{1,15}$/',
            'vehiculo_marca' => 'required|string|max:50',
            'vehiculo_modelo' => 'required|string|max:50',
            'vehiculo_anio' => "required|integer|min:1950|max:" . ($anioActual + 1),
            'problema' => 'required|string',
        ], [
            'correo.regex' => 'Correo inválido',
            'telefono.regex' => 'El teléfono solo puede contener hasta 15 dígitos numéricos, sin espacios.',
        ]);

        $solicitud = SolicitudReserva::create($data + ['estado' => 'pendiente']);

        $this->notificarAdmins($solicitud);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud registrada correctamente. Nos comunicaremos contigo pronto.',
            'data' => $solicitud,
        ], 201);
    }

    /**
     * Listado de solicitudes para el panel admin, opcionalmente filtrado por estado.
     */
    public function index(Request $request)
    {
        $query = SolicitudReserva::with('tipoDocumento')->latest();

        if ($request->filled('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        return response()->json($query->get());
    }

    /**
     * Marca una solicitud como atendida o rechazada.
     */
    public function updateEstado(Request $request, $id)
    {
        $data = $request->validate([
            'estado' => 'required|in:atendida,rechazada',
            'motivo_rechazo' => 'nullable|string|max:255',
        ]);

        $solicitud = SolicitudReserva::findOrFail($id);
        $solicitud->update([
            'estado' => $data['estado'],
            'motivo_rechazo' => $data['estado'] === 'rechazada' ? ($data['motivo_rechazo'] ?? null) : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $solicitud,
        ]);
    }

    /**
     * Conteo de solicitudes pendientes, usado por el modal de aviso del panel admin.
     */
    public function pendientesCount()
    {
        return response()->json([
            'count' => SolicitudReserva::where('estado', 'pendiente')->count(),
        ]);
    }

    /**
     * Notifica por push (FCM) a todos los administradores con token registrado.
     * Un fallo de envío nunca debe romper la creación de la solicitud.
     */
    private function notificarAdmins(SolicitudReserva $solicitud): void
    {
        try {
            $fcmService = app(FcmService::class);

            $admins = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'ADMIN'))
                ->whereNotNull('fcm_token')
                ->get();

            foreach ($admins as $admin) {
                $fcmService->enviarNotificacion(
                    $admin->fcm_token,
                    'Nueva solicitud de reserva',
                    "{$solicitud->vehiculo_marca} {$solicitud->vehiculo_modelo} ({$solicitud->vehiculo_anio}) — {$solicitud->problema}",
                    [
                        'tipo' => 'solicitud_reserva',
                        'id' => $solicitud->id,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::warning('Fallo al notificar solicitud de reserva a administradores: ' . $e->getMessage());
        }
    }
}
