<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecoveryCodeMail;
use Carbon\Carbon;

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
}
