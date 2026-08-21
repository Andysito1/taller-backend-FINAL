<?php

namespace App\Rules;

use App\Models\TipoDocumento;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida el número de documento según el tipo seleccionado (id_tipo_documento):
 * DNI/RUC/CE solo dígitos, Pasaporte alfanumérico, respetando longitud_exacta
 * y longitud_maxima definidas en la tabla tipos_documento.
 */
class NumeroDocumentoValido implements ValidationRule
{
    public function __construct(private readonly ?int $idTipoDocumento)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tipo = $this->idTipoDocumento ? TipoDocumento::find($this->idTipoDocumento) : null;
        if (!$tipo) {
            return;
        }

        $value = (string) $value;
        $esPasaporte = $tipo->abreviatura === 'PAS';

        if ($esPasaporte) {
            if (!ctype_alnum($value)) {
                $fail('El pasaporte debe ser un código alfanumérico, sin símbolos ni espacios.');
            }
        } elseif (!ctype_digit($value)) {
            $fail("El documento {$tipo->abreviatura} solo puede contener dígitos.");
        }

        if ($tipo->longitud_exacta && strlen($value) != $tipo->longitud_exacta) {
            $fail("El documento {$tipo->abreviatura} debe tener {$tipo->longitud_exacta} dígitos.");
        }
        if ($tipo->longitud_maxima && strlen($value) > $tipo->longitud_maxima) {
            $fail("El documento {$tipo->abreviatura} no puede exceder los {$tipo->longitud_maxima} caracteres.");
        }
    }
}
