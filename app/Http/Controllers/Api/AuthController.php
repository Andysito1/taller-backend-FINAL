<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RecoveryCodeMail;
use App\Mail\WelcomeMail;
use App\Models\Cliente;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $correo = strtolower(trim($request->correo));
        $user = Usuario::with('rol')->where('correo', $correo)->first();

        if (!$user || !Hash::check($request->password, $user->password) || !$user->activo) {
            return response()->json([
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'El correo es obligatorio y debe ser valido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $correo = strtolower(trim($request->correo));
        $user = Usuario::where('correo', $correo)->first();

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
                'message' => 'Te enviamos un codigo de recuperacion a tu correo.',
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el codigo de recuperacion por SMTP.', [
                'usuario_id' => $user->id,
                'correo' => $user->correo,
                'error' => $e->getMessage(),
            ]);

            $user->codigo_recuperacion = null;
            $user->codigo_expira_at = null;
            $user->save();

            return response()->json([
                'message' => 'No se pudo enviar el codigo de recuperacion.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => ['required', 'email'],
            'codigo' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Revisa el correo y el codigo enviado.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $this->findUserForRecovery($request->correo, $request->codigo);

        if (!$user || !$user->activo) {
            return response()->json([
                'message' => 'El codigo de recuperacion no es valido.',
            ], 400);
        }

        if (!$user->codigo_expira_at || $user->codigo_expira_at->isPast()) {
            return response()->json([
                'message' => 'El codigo de recuperacion ya vencio.',
            ], 400);
        }

        return response()->json([
            'message' => 'Codigo verificado correctamente.',
        ]);
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

        $user = $this->findUserForRecovery($request->correo, $request->codigo);

        if (!$user || !$user->activo) {
            return response()->json([
                'message' => 'El codigo de recuperacion no es valido.',
            ], 400);
        }

        if (!$user->codigo_expira_at || $user->codigo_expira_at->isPast()) {
            return response()->json([
                'message' => 'El codigo de recuperacion ya vencio.',
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->codigo_recuperacion = null;
        $user->codigo_expira_at = null;
        $user->save();
        $user->load('rol');

        return response()->json([
            'message' => 'Tu contrasena fue actualizada correctamente.',
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function enviarCodigoRecuperacion(Request $request)
    {
        $request->validate(['correo' => 'required|email|exists:usuarios,correo']);

        $correo = strtolower(trim($request->correo));
        $user = Usuario::where('correo', $correo)->first();

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->codigo_recuperacion = $codigo;
        $user->codigo_expira_at = Carbon::now()->addMinutes(15);
        $user->save();

        try {
            Mail::to($user->correo)->send(new RecoveryCodeMail($codigo, $user->nombre));

            return response()->json(['message' => 'Codigo enviado con exito a su correo']);
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el codigo de recuperacion por SMTP.', [
                'usuario_id' => $user->id,
                'correo' => $user->correo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'No se pudo enviar el correo',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function restablecerPassword(Request $request)
    {
        $request->validate([
            'correo' => 'required|email|exists:usuarios,correo',
            'codigo' => 'required|string|size:6',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = $this->findUserForRecovery($request->correo, $request->codigo);

        if (!$user) {
            return response()->json(['message' => 'Codigo de recuperacion invalido'], 400);
        }

        if (!$user->codigo_expira_at || Carbon::now()->gt($user->codigo_expira_at)) {
            return response()->json(['message' => 'El codigo ha expirado'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->codigo_recuperacion = null;
        $user->codigo_expira_at = null;
        $user->save();
        $user->load('rol');

        return response()->json([
            'message' => 'Contrasena actualizada con exito',
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->stateless()->redirect();
    }

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
            $user = Usuario::where('google_id', $googleUser->id)
                ->orWhere('correo', $googleUser->email)
                ->first();

            if (!$user) {
                $user = Usuario::create([
                    'nombre' => $googleUser->name,
                    'correo' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'id_rol' => 3,
                    'id_tipo_documento' => null,
                    'activo' => 1,
                    'password' => null,
                ]);

                Cliente::create(['id_usuario' => $user->id]);
                Mail::to($user->correo)->send(new WelcomeMail($user));
            } else {
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Autenticacion exitosa',
                'token' => $user->createToken('auth_token')->plainTextToken,
                'user' => $user->load('rol'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Error al procesar el usuario.', 'detalle' => $e->getMessage()], 500);
        }
    }

    private function findUserForRecovery(string $correo, string $codigo): ?Usuario
    {
        return Usuario::with('rol')
            ->where('correo', strtolower(trim($correo)))
            ->where('codigo_recuperacion', trim($codigo))
            ->first();
    }
}
