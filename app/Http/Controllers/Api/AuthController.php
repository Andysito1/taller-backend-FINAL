<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RecoveryCodeMail;
use App\Mail\WelcomeMail;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Cliente;
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

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'El correo es obligatorio y debe ser válido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Usuario::where('correo', $request->correo)->first();

        if (!$user || !$user->activo) {
            return response()->json([
                'message' => 'No existe una cuenta activa con ese correo.',
            ], 404);
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->codigo_recuperacion = $codigo;
        $user->codigo_expira_at = now()->addMinutes(15);
        $user->save();

        try {
            Mail::to($user->correo)->send(new RecoveryCodeMail($codigo, $user->nombre));

            return response()->json([
                'message' => 'Te enviamos un código de recuperación a tu correo.',
            ]);
        } catch (\Throwable $e) {
            $user->codigo_recuperacion = null;
            $user->codigo_expira_at = null;
            $user->save();

            return response()->json([
                'message' => 'No se pudo enviar el código de recuperación.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => ['required', 'email'],
            'codigo' => ['required', 'string', 'size:6'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Revisa los datos enviados.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Usuario::where('correo', $request->correo)
            ->where('codigo_recuperacion', $request->codigo)
            ->first();

        if (!$user || !$user->activo) {
            return response()->json([
                'message' => 'El código de recuperación no es válido.',
            ], 400);
        }

        if (!$user->codigo_expira_at || $user->codigo_expira_at->isPast()) {
            return response()->json([
                'message' => 'El código de recuperación ya venció.',
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->codigo_recuperacion = null;
        $user->codigo_expira_at = null;
        $user->save();

        return response()->json([
            'message' => 'Tu contraseña fue actualizada correctamente.',
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
                    'id_tipo_documento' => null,
                    'activo' => 1,
                    'password' => null,
                ]);

                // 2.1 Crear el registro en la tabla clientes para mantener la integridad
                Cliente::create(['id_usuario' => $user->id]);

                // 2.2 Enviar correo de bienvenida
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
