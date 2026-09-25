<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cotizacion_id', 'categoria', 'descripcion', 'unidad', 'cantidad', 'valor_unitario', 'orden',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function getValorTotalAttribute(): float
    {
        return round($this->cantidad * $this->valor_unitario, 2);
    }
}
