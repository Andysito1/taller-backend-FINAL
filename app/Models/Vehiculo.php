<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = [
        'id_cliente',
        'marca',
        'modelo',
        'anio',
        'placa',
        'imagen'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function ordenes()
    {
        return $this->hasMany(\App\Models\OrdenServicio::class, 'id_vehiculo');
    }
}
