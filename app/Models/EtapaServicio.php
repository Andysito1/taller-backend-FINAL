<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtapaServicio extends Model
{
    use HasFactory;

    protected $table = 'etapas_servicio';

    protected $fillable = [
        'id_orden',
        'etapa',
        'estado',
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenServicio::class, 'id_orden');
    }
}