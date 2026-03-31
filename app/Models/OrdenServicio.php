<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenServicio extends Model
{
    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'id_vehiculo',
        'id_mecanico',
        'titulo',
        'descripcion',
        'estado',
        'validacion_diagnostico',
        'fecha_inicio',
        'fecha_fin'
    ];

    // Relación con vehículo
    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }

    public function mecanico()
    {
        return $this->belongsTo(Usuario::class, 'id_mecanico');
    }

    // Relación con etapas
    public function etapas()
    {
        return $this->hasMany(EtapaServicio::class, 'id_orden');
    }

    // Relación con finanzas
    public function finanzas()
    {
        return $this->hasMany(FinanzaServicio::class, 'id_orden');
    }
}
