<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinanzaServicio extends Model
{
    use HasFactory;

    protected $table = 'finanzas_servicio';

    protected $fillable = [
        'id_orden',
        'concepto',
        'tipo',
        'monto',
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenServicio::class, 'id_orden');
    }
}