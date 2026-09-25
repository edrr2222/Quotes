<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Proyecto;
use App\Services\CotizacionSummaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CotizacionController extends Controller
{
    public function store(Proyecto $proyecto): RedirectResponse
    {
        abort_unless($proyecto->user_id === auth()->id(), 403);

        $cotizacion = Cotizacion::create([
            'proyecto_id' => $proyecto->id,
            'numero' => 'COT-'.now()->format('Ymd').'-'.str_pad((string) ($proyecto->cotizaciones()->count() + 1), 2, '0', STR_PAD_LEFT),
        ]);

        return redirect()->route('cotizaciones.edit', $cotizacion);
    }

    public function edit(Cotizacion $cotizacion, CotizacionSummaryService $summary): Response
    {
        $this->authorizeCotizacion($cotizacion);

        $cotizacion->load(['proyecto', 'especificacionItems', 'manoObraItems', 'materialItems']);

        return Inertia::render('Cotizaciones/Edit', [
            'cotizacion' => $cotizacion,
            'resumen' => $summary->resumen($cotizacion),
        ]);
    }

    public function update(Request $request, Cotizacion $cotizacion): RedirectResponse
    {
        $this->authorizeCotizacion($cotizacion);

        $data = $request->validate([
            'numero' => ['nullable', 'string', 'max:50'],
            'jornales' => ['required', 'integer', 'min:1'],
            'factor_prestacional' => ['required', 'numeric', 'min:1'],
            'imprevistos_mano_obra_pct' => ['required', 'numeric', 'min:0', 'max:1'],
            'transporte_pct' => ['required', 'numeric', 'min:0', 'max:1'],
            'herramienta_pct' => ['required', 'numeric', 'min:0', 'max:1'],
            'administracion_pct' => ['required', 'numeric', 'min:0', 'max:1'],
            'imprevistos_pct' => ['required', 'numeric', 'min:0', 'max:1'],
            'utilidad_pct' => ['required', 'numeric', 'min:0', 'max:1'],
            'iva_pct' => ['required', 'numeric', 'min:0', 'max:1'],
            'notas' => ['nullable', 'string'],

            'especificacion_items' => ['array'],
            'especificacion_items.*.categoria' => ['nullable', 'string', 'max:255'],
            'especificacion_items.*.descripcion' => ['required', 'string'],

            'mano_obra_items' => ['array'],
            'mano_obra_items.*.cargo' => ['required', 'string', 'max:255'],
            'mano_obra_items.*.numero_personas' => ['required', 'integer', 'min:1'],
            'mano_obra_items.*.jornal_basico' => ['required', 'numeric', 'min:0'],
            'mano_obra_items.*.arl_dia' => ['nullable', 'numeric', 'min:0'],

            'material_items' => ['array'],
            'material_items.*.categoria' => ['required', 'in:obra_civil,hidraulico,electrico'],
            'material_items.*.descripcion' => ['required', 'string', 'max:255'],
            'material_items.*.unidad' => ['required', 'string', 'max:20'],
            'material_items.*.cantidad' => ['required', 'numeric', 'min:0'],
            'material_items.*.valor_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($cotizacion, $data) {
            $cotizacion->update(collect($data)->except(['especificacion_items', 'mano_obra_items', 'material_items'])->toArray());

            $cotizacion->especificacionItems()->delete();
            foreach (($data['especificacion_items'] ?? []) as $i => $item) {
                $cotizacion->especificacionItems()->create([...$item, 'orden' => $i]);
            }

            $cotizacion->manoObraItems()->delete();
            foreach (($data['mano_obra_items'] ?? []) as $i => $item) {
                $cotizacion->manoObraItems()->create([...$item, 'orden' => $i]);
            }

            $cotizacion->materialItems()->delete();
            foreach (($data['material_items'] ?? []) as $i => $item) {
                $cotizacion->materialItems()->create([...$item, 'orden' => $i]);
            }
        });

        return back();
    }

    public function destroy(Cotizacion $cotizacion): RedirectResponse
    {
        $this->authorizeCotizacion($cotizacion);
        $proyecto = $cotizacion->proyecto;
        $cotizacion->delete();

        return redirect()->route('proyectos.show', $proyecto);
    }

    private function authorizeCotizacion(Cotizacion $cotizacion): void
    {
        abort_unless($cotizacion->proyecto->user_id === auth()->id(), 403);
    }
}
