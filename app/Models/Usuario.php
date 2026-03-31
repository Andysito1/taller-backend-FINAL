<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios'; // IMPORTANTE

    protected $fillable = [
        'nombre',
        'correo',
        'password',
        'id_rol',
        'activo',
        'telefono',
        'direccion',
        'id_tipo_documento',
        'numero_documento'
    ];

    protected $hidden = [
        'password'
    ];

    // Indicar que el campo de login es correo
    public function getAuthIdentifierName()
    {
        return 'correo';
    }

    public function rol()
    {
        return $this->belongsTo(Role::class, 'id_rol');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo_documento');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'id_usuario');
    }

    public function mecanico()
    {
        return $this->hasOne(Mecanico::class, 'id_usuario');
    }
}
