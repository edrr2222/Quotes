<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\Documento;
use App\Services\CotizacionSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    // Los 3 documentos que debe generar el sistema (ver CLAUDE.md):
    private const VISTAS = [
        'especificacion' => 'pdf.especificacion',
        'especificacion_mano_obra' => 'pdf.especificacion-mano-obra',
        'especificacion_materiales' => 'pdf.especificacion-materiales',
    ];

    public function generar(Cotizacion $cotizacion, string $tipo, CotizacionSummaryService $summary): RedirectResponse
    {
        abort_unless($cotizacion->proyecto->user_id === auth()->id(), 403);
        abort_unless(array_key_exists($tipo, self::VISTAS), 404, 'Tipo de documento no reconocido.');

        $cotizacion->load('proyecto', 'especificacionItems', 'manoObraItems', 'materialItems');
        $datos = [
            'cotizacion' => $cotizacion,
            'proyecto' => $cotizacion->proyecto,
            'especificacion' => $cotizacion->especificacionItems,
            'resumen' => $summary->resumen($cotizacion),
        ];

        $pdf = Pdf::loadView(self::VISTAS[$tipo], $datos)->setPaper('letter');

        $nombreArchivo = sprintf('%s_%s_%s.pdf', $tipo, str($cotizacion->proyecto->nombre)->slug(), $cotizacion->numero);
        $ruta = "documentos/{$cotizacion->id}/{$nombreArchivo}";
        Storage::put($ruta, $pdf->output());

        Documento::create([
            'cotizacion_id' => $cotizacion->id,
            'tipo' => $tipo,
            'ruta_archivo' => $ruta,
        ]);

        return back();
    }

    public function descargar(Documento $documento): StreamedResponse
    {
        abort_unless($documento->cotizacion->proyecto->user_id === auth()->id(), 403);

        return Storage::download($documento->ruta_archivo);
    }
}
