<?php

namespace App\Services;

use App\Models\Cotizacion;

/**
 * Calcula los costos de mano de obra de una cotización.
 *
 * Reglas (ver CLAUDE.md):
 * - Si el ítem trae `arl_dia` explícito (como cuando el usuario ajusta la tabla a mano,
 *   igual que se hizo en la cotización de Carmen de Apicalá), se usa ese valor tal cual.
 * - Si no trae `arl_dia`, se calcula con `factor_prestacional` de la cotización
 *   (costo_dia = jornal_basico * factor_prestacional).
 * - Los "jornales" (días trabajados) son un valor único por cotización (todas las personas
 *   trabajan el mismo número de jornales), tomado de `cotizacion.jornales`.
 */
class ManoObraCalculatorService
{
    public function calcularItem(Cotizacion $cotizacion, array $item): array
    {
        $jornales = $cotizacion->jornales;
        $jornalBasico = (float) $item['jornal_basico'];
        $personas = (int) $item['numero_personas'];

        $arlDia = isset($item['arl_dia']) && $item['arl_dia'] !== null
            ? (float) $item['arl_dia']
            : $jornalBasico * ($cotizacion->factor_prestacional - 1);

        $costoDiaPorPersona = $jornalBasico + $arlDia;
        $costoTotal = $costoDiaPorPersona * $jornales * $personas;

        return [
            'jornales' => $jornales,
            'arl_dia' => round($arlDia, 2),
            'arl_total_por_persona' => round($arlDia * $jornales, 2),
            'costo_total' => round($costoTotal, 2),
        ];
    }

    /**
     * @return array{items: array, total_mano_obra: float, imprevistos: float, total_con_imprevistos: float}
     */
    public function calcularCotizacion(Cotizacion $cotizacion): array
    {
        $items = [];
        $totalManoObra = 0.0;

        foreach ($cotizacion->manoObraItems as $item) {
            $calc = $this->calcularItem($cotizacion, $item->toArray());
            $items[] = array_merge($item->toArray(), $calc);
            $totalManoObra += $calc['costo_total'];
        }

        $imprevistos = round($totalManoObra * $cotizacion->imprevistos_mano_obra_pct, 2);

        return [
            'items' => $items,
            'total_mano_obra' => round($totalManoObra, 2),
            'imprevistos' => $imprevistos,
            'total_con_imprevistos' => round($totalManoObra + $imprevistos, 2),
        ];
    }
}
