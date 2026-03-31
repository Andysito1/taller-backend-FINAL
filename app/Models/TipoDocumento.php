<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'tipos_documento';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'longitud_exacta',
        'longitud_maxima'
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'id_tipo_documento');
    }
}