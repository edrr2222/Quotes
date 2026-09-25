<?php

namespace App\Services;

use App\Models\Cotizacion;

/**
 * Resumen económico completo de una cotización: materiales por categoría + mano de obra
 * + transporte + herramienta + AIU + IVA = total general.
 *
 * Los porcentajes (transporte_pct, herramienta_pct, administracion_pct, imprevistos_pct,
 * utilidad_pct, iva_pct) viven en la propia Cotizacion — nunca hardcodeados aquí — para que
 * cada proyecto pueda ajustarlos (p. ej. un municipio más lejano sube el % de transporte).
 */
class CotizacionSummaryService
{
    public function __construct(
        private readonly ManoObraCalculatorService $manoObra,
    ) {
    }

    public function materialesPorCategoria(Cotizacion $cotizacion): array
    {
        $porCategoria = ['obra_civil' => [], 'hidraulico' => [], 'electrico' => []];
        $totales = ['obra_civil' => 0.0, 'hidraulico' => 0.0, 'electrico' => 0.0];

        foreach ($cotizacion->materialItems as $item) {
            $total = round($item->cantidad * $item->valor_unitario, 2);
            $porCategoria[$item->categoria][] = array_merge($item->toArray(), ['valor_total' => $total]);
            $totales[$item->categoria] += $total;
        }

        return [
            'items' => $porCategoria,
            'subtotales' => array_map(fn ($v) => round($v, 2), $totales),
            'total_materiales' => round(array_sum($totales), 2),
        ];
    }

    public function resumen(Cotizacion $cotizacion): array
    {
        $mo = $this->manoObra->calcularCotizacion($cotizacion);
        $mat = $this->materialesPorCategoria($cotizacion);

        $costoDirecto = $mo['total_con_imprevistos'] + $mat['total_materiales'];
        $transporte = round($mat['total_materiales'] * $cotizacion->transporte_pct, 2);
        $herramienta = round($costoDirecto * $cotizacion->herramienta_pct, 2);
        $costoDirectoAjustado = $costoDirecto + $transporte + $herramienta;

        $administracion = round($costoDirectoAjustado * $cotizacion->administracion_pct, 2);
        $imprevistosGenerales = round($costoDirectoAjustado * $cotizacion->imprevistos_pct, 2);
        $utilidad = round($costoDirectoAjustado * $cotizacion->utilidad_pct, 2);
        $aiu = $administracion + $imprevistosGenerales + $utilidad;

        $subtotalAntesIva = $costoDirectoAjustado + $aiu;
        $iva = round($subtotalAntesIva * $cotizacion->iva_pct, 2);
        $totalGeneral = $subtotalAntesIva + $iva;

        return [
            'mano_obra' => $mo,
            'materiales' => $mat,
            'costo_directo' => round($costoDirecto, 2),
            'transporte' => $transporte,
            'herramienta' => $herramienta,
            'costo_directo_ajustado' => round($costoDirectoAjustado, 2),
            'administracion' => $administracion,
            'imprevistos_generales' => $imprevistosGenerales,
            'utilidad' => $utilidad,
            'aiu' => round($aiu, 2),
            'subtotal_antes_iva' => round($subtotalAntesIva, 2),
            'iva' => $iva,
            'total_general' => round($totalGeneral, 2),
        ];
    }
}
