<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudReserva extends Model
{
    protected $table = 'solicitudes_reserva';

    protected $fillable = [
        'id_tipo_documento',
        'numero_documento',
        'correo',
        'telefono',
        'vehiculo_marca',
        'vehiculo_modelo',
        'vehiculo_anio',
        'problema',
        'estado',
        'motivo_rechazo',
    ];

    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'id_tipo_documento');
    }
}
