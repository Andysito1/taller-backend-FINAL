<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\RecoveryCodeMail;
use App\Mail\WelcomeMail;
use Carbon\Carbon;
use App\Models\Usuario;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Buscar usuario por correo con la relación del rol
        $user = \App\Models\Usuario::with('rol')->where('correo', $request->correo)->first();

        // Validar credenciales y que esté activo
        if (!$user || !Hash::check($request->password, $user->password) || !$user->activo) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        // Crear token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Genera un código de 6 dígitos y lo envía por Resend
     */
    public function enviarCodigoRecuperacion(Request $request)
    {
        $request->validate(['correo' => 'required|email|exists:usuarios,correo']);

        $user = \App\Models\Usuario::where('correo', $request->correo)->first();
        
        // Generar código aleatorio de 6 dígitos
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Guardar código con expiración de 15 minutos
        $user->codigo_recuperacion = $codigo;
        $user->codigo_expira_at = Carbon::now()->addMinutes(15);
        $user->save();

        try {
            Mail::to($user->correo)->send(new RecoveryCodeMail($codigo, $user->nombre));
            return response()->json(['message' => 'Código enviado con éxito a su correo']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'No se pudo enviar el correo',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida el código y actualiza la contraseña
     */
    public function restablecerPassword(Request $request)
    {
        $request->validate([
            'correo' => 'required|email|exists:usuarios,correo',
            'codigo' => 'required|string|size:6',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = \App\Models\Usuario::with('rol')
            ->where('correo', $request->correo)
            ->where('codigo_recuperacion', $request->codigo)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Código de recuperación inválido'], 400);
        }

        // Verificar si el código ha expirado
        if (Carbon::now()->gt($user->codigo_expira_at)) {
            return response()->json(['message' => 'El código ha expirado'], 400);
        }

        // Actualizar contraseña y limpiar campos de recuperación
        $user->password = Hash::make($request->password);
        $user->codigo_recuperacion = null;
        $user->codigo_expira_at = null;
        $user->save();

        // Opcional: Iniciar sesión automáticamente después del cambio
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Contraseña actualizada con éxito',
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Redirige al usuario a la página de autenticación de Google.
     */
    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');
        return $driver->stateless()->redirect();
    }

    /**
     * Maneja la respuesta de Google después de la autenticación.
     */
    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al autenticar con Google.', 'detalle' => $e->getMessage()], 400);
        }

        DB::beginTransaction();
        try {
            // 1. Buscar por google_id o por correo
            $user = Usuario::where('google_id', $googleUser->id)
                ->orWhere('correo', $googleUser->email)
                ->first();

            if (!$user) {
                // 2. Crear nuevo usuario si no existe
                $user = Usuario::create([
                    'nombre' => $googleUser->name,
                    'correo' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'id_rol' => 3, // CLIENTE
                    'id_tipo_documento' => null, // Opcional: podrías asignar uno por defecto si fuera necesario
                    'activo' => 1,
                    'password' => null,
                ]);

                // Enviar correo de bienvenida
                // Nota: Google ya verificó el correo, por lo que no necesita un código adicional.
                Mail::to($user->correo)->send(new WelcomeMail($user));
            } else {
                // 3. Actualizar datos si ya existe (vincular)
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
            }

            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;
            
            return response()->json([
                'message' => 'Autenticación exitosa',
                'token' => $token,
                'user' => $user->load('rol')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al procesar el usuario.', 'detalle' => $e->getMessage()], 500);
        }
    }
}
