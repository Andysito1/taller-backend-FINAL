<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaReporte extends Model
{
    use HasFactory;

    protected $table = 'auditoria_reportes';

    protected $fillable = [
        'id_usuario',
        'tipo_reporte',
        'filtros_aplicados',
        'formato',
        'total_registros',
    ];

    protected $casts = [
        'filtros_aplicados' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}