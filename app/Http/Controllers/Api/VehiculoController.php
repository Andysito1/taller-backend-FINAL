<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehiculo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class VehiculoController extends Controller
{
    // Listar todos los vehículos (para admin/mecanico)
    public function index(Request $request)
    {
        $vehiculos = \App\Models\Vehiculo::with(['cliente.usuario', 'ordenes.mecanico', 'ordenes.etapas'])
            ->get();

        return response()->json($vehiculos);
    }

    public function misVehiculos(Request $request)
    {
        $user = $request->user();

        // Buscar el cliente asociado al usuario autenticado
        $cliente = Cliente::where('id_usuario', $user->id)->first();

        if (!$cliente) {
            // Si no se encuentra un cliente para este usuario, devolver un array vacío.
            return response()->json([]);
        }

        $vehiculos = Vehiculo::where('id_cliente', $cliente->id)
            ->get();

        // Devolver directamente la colección. Laravel la convertirá en un array JSON [...]
        return response()->json($vehiculos);
    }

    // Crear un nuevo vehículo
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Si el usuario es CLIENTE, forzamos su id_cliente para evitar errores del front
        if ($user->rol->nombre === 'CLIENTE') {
            $cliente = Cliente::where('id_usuario', $user->id)->first();
            $request->merge(['id_cliente' => $cliente->id]);
        }

        $validData = $request->validate([
            'id_cliente' => 'required|exists:clientes,id',
            'marca'      => 'required|string|max:50',
            'modelo'     => 'required|string|max:50',
            'anio'       => 'required|integer',
            'placa'      => 'required|string|max:20|unique:vehiculos,placa',
            'imagen'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        // Lógica para procesar y guardar la imagen
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('vehiculos', 'public');
            $validData['imagen'] = $path;
        }

        $vehiculo = Vehiculo::create($validData);

        return response()->json([
            'message' => 'Vehículo registrado correctamente',
            'vehiculo' => $vehiculo
        ], 201);
    }

    // Actualizar vehículo
    public function update(Request $request, $id)
    {
        $vehiculo = Vehiculo::findOrFail($id);

        $validData = $request->validate([
            'id_cliente' => 'required|exists:clientes,id',
            'marca'      => 'required|string|max:50',
            'modelo'     => 'required|string|max:50',
            'anio'       => 'required|integer',
            'placa'      => 'required|string|max:20|unique:vehiculos,placa,' . $id,
            'imagen'     => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe para no llenar el disco
            if ($vehiculo->imagen) {
                Storage::disk('public')->delete($vehiculo->imagen);
            }
            $path = $request->file('imagen')->store('vehiculos', 'public');
            $validData['imagen'] = $path;
        }

        $vehiculo->update($validData);

        return response()->json(['message' => 'Vehículo actualizado', 'vehiculo' => $vehiculo]);
    }

    // Eliminar vehículo
    public function destroy($id)
    {
        $vehiculo = Vehiculo::findOrFail($id);
        if ($vehiculo->imagen) {
            Storage::disk('public')->delete($vehiculo->imagen);
        }
        $vehiculo->delete();
        return response()->json(['message' => 'Vehículo eliminado correctamente']);
    }
}
