<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        return Storage::disk('public')->url($this->archivo_url);
    }
}
