<?php

namespace App\Http\Controllers;

use App\Models\Plano;
use App\Models\Proyecto;
use App\Services\PlanoAnalyzerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PlanoController extends Controller
{
    public function store(Request $request, Proyecto $proyecto): RedirectResponse
    {
        abort_unless($proyecto->user_id === auth()->id(), 403);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'tipo' => ['required', 'in:arquitectonico,estructural,hidraulico,electrico,otro'],
        ]);

        $archivo = $request->file('archivo');
        $ruta = $archivo->store("planos/{$proyecto->id}");

        Plano::create([
            'proyecto_id' => $proyecto->id,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'tipo' => $request->input('tipo'),
        ]);

        return back();
    }

    public function analizar(Plano $plano, PlanoAnalyzerService $analyzer): RedirectResponse
    {
        abort_unless($plano->proyecto->user_id === auth()->id(), 403);

        $plano->update(['estado_analisis' => 'procesando']);

        try {
            $resultado = $analyzer->analizar($plano);
            $plano->update(['estado_analisis' => 'listo', 'analisis_json' => $resultado]);
        } catch (\Throwable $e) {
            $plano->update(['estado_analisis' => 'error', 'analisis_json' => ['error' => $e->getMessage()]]);
        }

        return back();
    }

    public function destroy(Plano $plano): RedirectResponse
    {
        abort_unless($plano->proyecto->user_id === auth()->id(), 403);

        Storage::delete($plano->ruta_archivo);
        $plano->delete();

        return back();
    }
}
