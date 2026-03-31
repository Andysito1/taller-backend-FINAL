<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriaNotificacion extends Model
{
    protected $table = 'auditoria_notificaciones';

    protected $fillable = [
        'id_notificacion',
        'payload_enviado',
        'canal',
        'exito',
        'error_mensaje'
    ];

    protected $casts = [
        'payload_enviado' => 'array',
        'exito' => 'boolean'
    ];
}