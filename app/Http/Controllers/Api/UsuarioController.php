<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MarketingReminderMail;
use App\Models\Cliente;
use App\Models\Mecanico;
use App\Models\Role;
use App\Models\TipoDocumento;
use App\Models\Usuario;
use App\Services\BrevoMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use GuzzleHttp\Client;

class UsuarioController extends Controller
{
    public function __construct(protected BrevoMailer $brevoMailer)
    {
    }
    // Listar usuarios
    public function index()
    {
        $usuarios = Usuario::with(['rol', 'tipoDocumento'])->get();

        return response()->json($usuarios);
    }

    // Listar todos los tipos de documento (para los selects del frontend)
    public function indexTiposDocumento()
    {
        return response()->json(TipoDocumento::all());
    }

    // Crear usuario
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255', // Aumentamos a 255 por si es Razón Social larga
            'correo' => 'required|email|unique:usuarios,correo',
            'password' => 'required|min:6',
            'id_rol' => 'required|exists:roles,id',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'especialidad' => 'nullable|string' // Opcional para mecánicos
        ]);

        // Verificar roles
        $rol = Role::find($request->id_rol);
        $esCliente = $rol && $rol->nombre === 'CLIENTE';
        $esMecanico = $rol && $rol->nombre === 'MECANICO';
        $esAdmin = $rol && $rol->nombre === 'ADMIN';

        /* 
         * LÓGICA DE VALIDACIÓN POR ROL:
         * - Admin: No requiere documentos ni dirección (solo usuario base).
         * - Cliente/Mecanico: Requieren documentos, teléfono y dirección.
         */
        if ($esCliente || $esMecanico) {
            $request->validate([
                'telefono' => 'required|string',
                'direccion' => 'required|string',
                'id_tipo_documento' => 'required|exists:tipos_documento,id',
                'numero_documento' => [
                    'required',
                    'unique:usuarios,numero_documento',
                    function ($attribute, $value, $fail) use ($request) {
                        $tipo = TipoDocumento::find($request->id_tipo_documento);
                        if (!$tipo) return;

                        if ($tipo->longitud_exacta && strlen($value) != $tipo->longitud_exacta) {
                            $fail("El documento {$tipo->abreviatura} debe tener {$tipo->longitud_exacta} caracteres.");
                        }
                        if ($tipo->longitud_maxima && strlen($value) > $tipo->longitud_maxima) {
                            $fail("El documento {$tipo->abreviatura} no puede exceder los {$tipo->longitud_maxima} caracteres.");
                        }
                    }
                ]
            ]);
        }

        DB::beginTransaction();

        try {

            $usuario = Usuario::create([
                'nombre' => $request->nombre,
                'correo' => $request->correo,
                'password' => Hash::make($request->password),
                'id_rol' => $request->id_rol,
                'activo' => 1,
                'telefono' => $request->telefono,
                'direccion' => $request->direccion,
                'id_tipo_documento' => $request->id_tipo_documento,
                'numero_documento' => $request->numero_documento,
            ]);

            /*
             * DISTRIBUCIÓN DE DATOS SEGÚN ROL
             */

            if ($esCliente) {

                Cliente::create([
                    'id_usuario' => $usuario->id
                ]);
            }

            if ($esMecanico) {
                // Si es mecánico, agregamos la especialidad (campo único de este rol)
                Mecanico::create([
                    'id_usuario' => $usuario->id,
                    'especialidad' => $request->especialidad ?? 'General'
                ]);
            }
            
            // Si es ADMIN, no hacemos nada extra porque sus datos están completos en la tabla 'usuarios'

            DB::commit();

            return response()->json([
                'message' => 'Usuario creado correctamente',
                'usuario' => $usuario->load(['rol', 'tipoDocumento'])
            ], 201);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Error al crear usuario',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    // Actualizar usuario
    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo,' . $id,
            'id_rol' => 'required|exists:roles,id',
            'password' => 'nullable|min:6'
        ]);

        $data = $request->only(['nombre', 'correo', 'id_rol', 'telefono', 'direccion', 'id_tipo_documento', 'numero_documento']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        // Si es mecánico, actualizar especialidad
        if ($usuario->id_rol == 2) { // Asumiendo 2 es MECANICO
            Mecanico::updateOrCreate(
                ['id_usuario' => $usuario->id],
                ['especialidad' => $request->especialidad ?? 'General']
            );
        }

        return response()->json(['message' => 'Usuario actualizado', 'usuario' => $usuario->load('rol')]);
    }

    // Eliminar usuario
    public function destroy($id)
    {
        $usuario = Usuario::findOrFail($id);
        
        // Evitar que el admin se borre a sí mismo si fuera necesario
        if (Auth::id() === $usuario->id) {
            return response()->json(['error' => 'No puedes eliminar tu propia cuenta'], 400);
        }

        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    // Activar / Desactivar usuario
    public function toggleActivo($id)
    {
        $usuario = Usuario::findOrFail($id);

        $usuario->activo = !$usuario->activo;
        $usuario->save();

        return response()->json([
            'message' => 'Estado actualizado',
            'activo' => $usuario->activo
        ]);
    }

    public function enviarRecordatorioCorreo(Request $request, $id)
    {
        $request->validate([
            'template' => ['nullable', Rule::in(['suave', 'intenso', 'persuasiva'])],
        ]);

        $usuario = Usuario::with('rol')->findOrFail($id);

        if (!$usuario->correo) {
            return response()->json([
                'message' => 'El cliente no tiene un correo registrado.',
            ], 422);
        }

        if (!$usuario->rol || $usuario->rol->nombre !== 'CLIENTE') {
            return response()->json([
                'message' => 'Solo se pueden enviar recordatorios a clientes.',
            ], 422);
        }

        $template = $request->input('template', 'suave');

        $contenido = in_array($template, ['intenso', 'persuasiva'], true)
            ? [
                'asunto' => 'Los mecánicos extrañan tu auto, ¿no quieres arreglar algo?',
                'titulo' => 'Tu auto merece una visita a Xtreme Performance',
                'subtitulo' => 'Los mecánicos extrañan tu auto y nosotros queremos verlo de nuevo en el taller.',
                'mensaje' => 'Si notas algún ruido, vibración o simplemente quieres una mejora, este es el momento ideal para traerlo.',
                'cta_texto' => 'Agendar revisión',
            ]
            : [
                'asunto' => '¿No te hace falta alguna mejora?',
                'titulo' => 'Tu siguiente mejora está esperándote',
                'subtitulo' => 'En Xtreme Performance siempre hay una mejora posible para tu vehículo.',
                'mensaje' => 'Aprovecha para revisar rendimiento, seguridad o estética y vuelve a sentir la diferencia.',
                'cta_texto' => 'Ver opciones de mejora',
            ];

        $contenido['cta_url'] = rtrim((string) config('app.frontend_url', config('app.url')), '/') . '/contacto';

        try {
            $this->brevoMailer->sendMailable($usuario->correo, new MarketingReminderMail($usuario, $contenido));

            return response()->json([
                'message' => 'Correo de recordatorio enviado correctamente.',
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el recordatorio por SMTP.', [
                'usuario_id' => $usuario->id,
                'correo' => $usuario->correo,
                'template' => $template,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo enviar el correo de recordatorio.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    // Listar solo mecánicos
    public function enviarRecordatorioMarketing(Request $request, $id)
    {
        return $this->enviarRecordatorioCorreo($request, $id);
    }

    public function indexMecanicos()
    {
        // Optimización: Consultamos la tabla mecanicos directamente y traemos los datos del usuario
        $mecanicos = Mecanico::with(['usuario.rol', 'usuario.tipoDocumento'])->get();

        return response()->json($mecanicos);
    }

    /* Consultar DNI/RUC */
    public function consultarDocumento(Request $request)
    {
        $request->validate([
            'id_tipo_documento' => 'required|exists:tipos_documento,id',
            'numero' => 'required|string'
        ]);

        $tipoDoc = TipoDocumento::find($request->id_tipo_documento);
        
        if (!$tipoDoc) {
            return response()->json(['error' => 'Tipo de documento no válido'], 400);
        }

        $abreviatura = $tipoDoc->abreviatura;
        $numero = $request->numero;

        if (in_array($abreviatura, ['CE', 'PAS'])) {
             return response()->json([
                'success' => true,
                'data' => ['nombre' => '', 'direccion' => '']
            ]);
        }

        // Token de apis.net.pe
        $token = env('APIS_NET_PE_TOKEN');
        if (!$token) {
            return response()->json(['error' => 'Configuración de API incompleta (Token faltante)'], 500);
        }

        $client = new Client(['base_uri' => 'https://api.decolecta.com', 'verify' => false]);

        $parameters = [
            'http_errors' => false,
            'connect_timeout' => 5,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'User-Agent' => 'laravel/guzzle',
                'Accept' => 'application/json',
            ],
            'query' => ['numero' => $numero]
        ];

        // Definir endpoint según el tipo
        if ($abreviatura === 'DNI') {
            $uri = '/v1/reniec/dni';
        } else {
            $uri = '/v1/sunat/ruc';
        }

        try {
            $res = $client->request('GET', $uri, $parameters);
            $response = json_decode($res->getBody()->getContents(), true);

            // Validar si la API retornó error o no encontró datos
            if (isset($response['error']) || empty($response)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El documento no fue encontrado en los registros oficiales.'
                ], 422); // 422 es más apropiado para errores de semántica/datos
            }

            $resultado = [];

            if ($abreviatura === 'DNI') {
                // Nuevo formato Decolecta para DNI
                $resultado['nombre'] = $response['full_name'] ?? '';
                $resultado['direccion'] = ''; 
            } else {
                $resultado['nombre'] = $response['razon_social'] ?? '';
                $resultado['direccion'] = $response['direccion'] ?? '';
            }

            return response()->json([
                'success' => true,
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al conectar con la API',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
    public function perfil(Request $request)
    {
        // $request->user() obtiene el usuario identificado por el token
        $usuario = $request->user()->load(['rol', 'tipoDocumento']); // Aseguramos que el rol y tipo doc estén cargados
        
        // Cargamos la información específica del rol
        if ($usuario->rol->nombre === 'CLIENTE') {
            $usuario->load('cliente');
        } else if ($usuario->rol->nombre === 'MECANICO') {
            $usuario->load('mecanico');
        }

        return response()->json($usuario);
    }

    public function updateFcmToken(Request $request) {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'Token actualizado correctamente']);
    }
}
