<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenciaServicio extends Model
{
    use HasFactory;

    protected $table = 'evidencias_servicio';

    protected $fillable = [
        'id_etapa',
        'tipo',
        'archivo_url',
        'descripcion',
    ];

    protected $appends = ['url'];

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(EtapaServicio::class, 'id_etapa');
    }

    public function getUrlAttribute(): string
    {
        // Storage::disk('public')->url() arma la URL con APP_URL, que en este
        // entorno suele quedar en su valor por defecto (http://localhost) y
        // genera imágenes rotas para la app móvil. Usamos siempre el dominio
        // público real del backend, igual que ya hace el cliente Flutter para
        // las imágenes de vehículos.
        $base = 'https://taller-backend-final-production.up.railway.app';
        return $base . '/storage/' . ltrim($this->archivo_url, '/');
    }
}
