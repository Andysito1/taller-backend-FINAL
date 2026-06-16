<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'correo',
        'password',
        'id_rol',
        'activo',
        'fcm_token',
        'codigo_recuperacion',
        'codigo_expira_at',
        'telefono',
        'direccion',
        'id_tipo_documento',
        'numero_documento',
        'google_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'codigo_recuperacion',
    ];

    protected $casts = [
        'codigo_expira_at' => 'datetime',
        'activo' => 'boolean',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'id_rol');
    }

    public function reportesGenerados(): HasMany
    {
        return $this->hasMany(AuditoriaReporte::class, 'id_usuario');
    }

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo_documento');
    }

    public function cliente(): HasOne
    {
        return $this->hasOne(Cliente::class, 'id_usuario');
    }

    public function mecanico(): HasOne
    {
        return $this->hasOne(Mecanico::class, 'id_usuario');
    }
}