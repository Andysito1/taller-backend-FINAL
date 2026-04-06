<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\OrdenServicioController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\EtapaServicioController;
use App\Http\Controllers\Api\FinanzaServicioController;
use App\Http\Controllers\Api\SeguimientoController;
use App\Http\Controllers\Api\ConfiguracionUsuarioController;
use App\Http\Controllers\Api\NotificacionController;

Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['csrf' => 'ok']);
});

Route::post('/login', [AuthController::class, 'login']);

Route::get('/login', function () {
    return response()->json(['message' => 'No autorizado. Por favor inicie sesión.'], 401);
})->name('login');

// Rutas para cualquier usuario autenticado
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/perfil', [UsuarioController::class, 'perfil']);
    Route::post('/usuarios/fcm-token', [UsuarioController::class, 'updateFcmToken']);
    // Route::post('/send-message', [ChatBotController::class, 'sendMessage']);
});

// Rutas para Administradores
Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function () {
    Route::post('/ordenes', [OrdenServicioController::class, 'store']);
    Route::get('/ordenes-servicio', [OrdenServicioController::class, 'index']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
    Route::put('/usuarios/{id}/toggle', [UsuarioController::class, 'toggleActivo']);
    Route::get('/mecanicos', [UsuarioController::class, 'indexMecanicos']);
    Route::get('/clientes', [ClienteController::class, 'index']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/consulta-documento', [UsuarioController::class, 'consultarDocumento']);
    Route::post('/notificaciones', [NotificacionController::class, 'store']);
    Route::get('tipos-documento', [UsuarioController::class, 'indexTiposDocumento']);
});
    
    // Rutas compartidas para Admin y Cliente
Route::middleware(['auth:sanctum', 'role:ADMIN,CLIENTE'])->group(function () {
    Route::get('/ordenes', [OrdenServicioController::class, 'index']);
    Route::get('/ordenes/{id}', [OrdenServicioController::class, 'show']);
    Route::patch('/ordenes/{id}', [OrdenServicioController::class, 'update']); // Soluciona error 405 PATCH
    Route::get('/orden-servicio/{id}', [OrdenServicioController::class, 'show']); // Alias para compatibilidad con la App Móvil
    Route::get('/seguimiento/{id_vehiculo}', [SeguimientoController::class, 'show']);
    Route::post('etapa-servicio/validar-diagnostico/{idOrden}', [EtapaServicioController::class, 'validarDiagnostico']);
});

// Rutas para Mecánicos
Route::middleware(['auth:sanctum', 'role:MECANICO'])->group(function () {
    Route::get('/mis-ordenes', [OrdenServicioController::class, 'misOrdenes']);
    Route::put('/ordenes/{id}/estado', [OrdenServicioController::class, 'update']);
});

// Rutas para Clientes
Route::middleware(['auth:sanctum', 'role:CLIENTE'])->group(function () {

    //Route::get('/mi-seguimiento', [OrdenServicioController::class, 'miSeguimiento']);
    Route::get('/mis-vehiculos', [VehiculoController::class, 'misVehiculos']);
    Route::get('/vehiculos/{id}/seguimiento', [OrdenServicioController::class, 'seguimientoVehiculo']);
    Route::get('/finanzas/{id_vehiculo}', [FinanzaServicioController::class, 'getPorVehiculo']);
    Route::get('/historial/{id_vehiculo}', [OrdenServicioController::class, 'historialPorVehiculo']);
    Route::get('/configuracion', [ConfiguracionUsuarioController::class, 'show']);
    Route::post('/configuracion', [ConfiguracionUsuarioController::class, 'update']);

    // Rutas de Notificaciones para el Cliente
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::put('/notificaciones/{id}/leer', [NotificacionController::class, 'marcarLeida']);
    Route::put('/notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
});

// Rutas compartidas para Admin y Mecánico
Route::middleware(['auth:sanctum', 'role:ADMIN,MECANICO'])->group(function () {

    Route::post('/vehiculos', [VehiculoController::class, 'store']);
    Route::get('/vehiculos', [VehiculoController::class, 'index']);
    Route::put('/vehiculos/{id}', [VehiculoController::class, 'update']);
    Route::delete('/vehiculos/{id}', [VehiculoController::class, 'destroy']);
    Route::patch('/etapa-servicio/{id}', [EtapaServicioController::class, 'update']);
    Route::post('/finanzas', [FinanzaServicioController::class, 'store']);
});