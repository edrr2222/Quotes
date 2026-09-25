<?php

use App\Http\Controllers\CotizacionController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\PlanoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProyectoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'))->name('welcome');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ProyectoController::class, 'index'])->name('dashboard');

    Route::resource('proyectos', ProyectoController::class)
        ->except(['create', 'edit'])
        ->parameters(['proyectos' => 'proyecto']);

    Route::post('proyectos/{proyecto}/planos', [PlanoController::class, 'store'])->name('planos.store');
    Route::post('planos/{plano}/analizar', [PlanoController::class, 'analizar'])->name('planos.analizar');
    Route::delete('planos/{plano}', [PlanoController::class, 'destroy'])->name('planos.destroy');

    Route::post('proyectos/{proyecto}/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
    Route::get('cotizaciones/{cotizacion}/editar', [CotizacionController::class, 'edit'])->name('cotizaciones.edit');
    Route::put('cotizaciones/{cotizacion}', [CotizacionController::class, 'update'])->name('cotizaciones.update');
    Route::delete('cotizaciones/{cotizacion}', [CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');

    Route::post('cotizaciones/{cotizacion}/documentos/{tipo}', [DocumentoController::class, 'generar'])->name('documentos.generar');
    Route::get('documentos/{documento}/descargar', [DocumentoController::class, 'descargar'])->name('documentos.descargar');

    // Rutas de perfil que Breeze genera (auth/perfil) — se conservan del instalador
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
