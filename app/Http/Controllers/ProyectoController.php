<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProyectoController extends Controller
{
    public function index(): Response
    {
        $proyectos = Proyecto::query()
            ->where('user_id', auth()->id())
            ->withCount('planos', 'cotizaciones')
            ->latest()
            ->get();

        return Inertia::render('Proyectos/Index', ['proyectos' => $proyectos]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $proyecto = Proyecto::create([...$data, 'user_id' => auth()->id()]);

        return redirect()->route('proyectos.show', $proyecto);
    }

    public function show(Proyecto $proyecto): Response
    {
        $this->authorizeProyecto($proyecto);

        $proyecto->load(['planos', 'cotizaciones' => fn ($q) => $q->latest()]);

        return Inertia::render('Proyectos/Show', ['proyecto' => $proyecto]);
    }

    public function update(Request $request, Proyecto $proyecto): RedirectResponse
    {
        $this->authorizeProyecto($proyecto);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['required', 'in:borrador,en_cotizacion,cotizado,aprobado,archivado'],
        ]);

        $proyecto->update($data);

        return back();
    }

    public function destroy(Proyecto $proyecto): RedirectResponse
    {
        $this->authorizeProyecto($proyecto);
        $proyecto->delete();

        return redirect()->route('proyectos.index');
    }

    private function authorizeProyecto(Proyecto $proyecto): void
    {
        abort_unless($proyecto->user_id === auth()->id(), 403);
    }
}
