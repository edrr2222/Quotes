<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    use HasFactory;

    // El pluralizador de Laravel (inglés) daría "cotizacions".
    protected $table = 'cotizaciones';

    protected $fillable = [
        'proyecto_id', 'numero', 'jornales', 'factor_prestacional',
        'imprevistos_mano_obra_pct', 'transporte_pct', 'herramienta_pct',
        'administracion_pct', 'imprevistos_pct', 'utilidad_pct', 'iva_pct',
        'estado', 'notas',
    ];

    protected $casts = [
        'factor_prestacional' => 'float',
        'imprevistos_mano_obra_pct' => 'float',
        'transporte_pct' => 'float',
        'herramienta_pct' => 'float',
        'administracion_pct' => 'float',
        'imprevistos_pct' => 'float',
        'utilidad_pct' => 'float',
        'iva_pct' => 'float',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function especificacionItems(): HasMany
    {
        return $this->hasMany(EspecificacionItem::class)->orderBy('orden');
    }

    public function manoObraItems(): HasMany
    {
        return $this->hasMany(ManoObraItem::class)->orderBy('orden');
    }

    public function materialItems(): HasMany
    {
        return $this->hasMany(MaterialItem::class)->orderBy('orden');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }
}
