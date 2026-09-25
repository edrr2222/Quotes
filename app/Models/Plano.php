<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plano extends Model
{
    use HasFactory;

    protected $fillable = [
        'proyecto_id', 'nombre_original', 'ruta_archivo', 'tipo', 'estado_analisis', 'analisis_json',
    ];

    protected $casts = [
        'analisis_json' => 'array',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
