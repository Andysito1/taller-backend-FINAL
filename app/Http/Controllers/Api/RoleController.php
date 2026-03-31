<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Muestra una lista de todos los roles.
     */
    public function index()
    {
        return Role::all();
    }
}