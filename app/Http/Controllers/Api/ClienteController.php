<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Muestra la lista de clientes con su usuario asociado.
     */
    public function index()
    {
        // 'usuario' es el nombre de la relación en el modelo Cliente
        return Cliente::with('usuario')->get();
    }

    /**
     * Muestra un cliente específico.
     */
    public function show($id)
    {
        return Cliente::with('usuario')->findOrFail($id);
    }
}
