<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManoObraItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cotizacion_id', 'cargo', 'numero_personas', 'jornal_basico', 'arl_dia', 'orden',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(Cotizacion::class);
    }
}
