<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EspecificacionItem extends Model
{
    use HasFactory;

    protected $fillable = ['cotizacion_id', 'categoria', 'descripcion', 'orden'];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
