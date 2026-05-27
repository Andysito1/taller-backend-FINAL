<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenServicio extends Model
{
    use HasFactory;

    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'id_vehiculo',
        'id_mecanico',
        'id_servicio', 
        'titulo',
        'descripcion',
        'estado',
        'costo_total', 
        'fecha_inicio',
        'fecha_fin',
        'validacion_diagnostico'
    ];

    protected $casts = [
        'costo_total' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'id_vehiculo');
    }

    public function mecanico(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_mecanico');
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(EtapaServicio::class, 'id_orden');
    }

    public function finanzas(): HasMany
    {
        return $this->hasMany(FinanzaServicio::class, 'id_orden');
    }
}