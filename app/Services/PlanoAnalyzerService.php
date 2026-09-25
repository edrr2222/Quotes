<?php

namespace App\Services;

use App\Models\Plano;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * Lee un plano PDF y extrae texto/cotas usando la API de Anthropic (visión).
 *
 * Requiere el binario `pdftoppm` (parte de poppler-utils) instalado en el servidor para
 * convertir el PDF a imagen — es lo mismo que se usó a mano para leer las cotas de los
 * planos de la piscina de Carmen de Apicalá. Si no está instalado:
 *   Ubuntu/Debian: sudo apt install poppler-utils
 *   macOS:         brew install poppler
 *
 * Si ANTHROPIC_API_KEY no está configurada, `disponible()` devuelve false y el controlador
 * debe deshabilitar el botón de análisis en vez de fallar.
 */
class PlanoAnalyzerService
{
    private const MODEL = 'claude-sonnet-4-6';

    public function disponible(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    /**
     * Convierte cada página del PDF a JPEG y las guarda junto al archivo original.
     *
     * @return string[] rutas absolutas de las imágenes generadas
     */
    public function convertirAImagenes(Plano $plano): array
    {
        $pdfPath = Storage::path($plano->ruta_archivo);
        $outDir = dirname($pdfPath);
        $prefix = $outDir.'/'.pathinfo($pdfPath, PATHINFO_FILENAME).'_pag';

        Process::run(['pdftoppm', '-jpeg', '-r', '150', $pdfPath, $prefix])->throw();

        $imagenes = glob($prefix.'-*.jpg') ?: glob($prefix.'-*.jpeg') ?: [];
        sort($imagenes);

        return $imagenes;
    }

    /**
     * Envía las imágenes del plano a Claude y pide un JSON estructurado con lo que se
     * alcance a leer: tipo de plano, cotas, profundidades, materiales/equipos en leyendas.
     *
     * Igual que hicimos manualmente: no se asume que el plano quede perfectamente leído;
     * el resultado es un borrador para que el usuario lo revise, no una verdad absoluta.
     */
    public function analizar(Plano $plano): array
    {
        if (! $this->disponible()) {
            throw new \RuntimeException('ANTHROPIC_API_KEY no está configurada.');
        }

        $imagenes = $this->convertirAImagenes($plano);

        if (empty($imagenes)) {
            throw new \RuntimeException('No se pudo convertir el PDF a imagen (¿está instalado poppler-utils?).');
        }

        $content = [[
            'type' => 'text',
            'text' => <<<'PROMPT'
                Eres un asistente que ayuda a un contratista de construcción en Colombia a leer
                planos de obra (arquitectónicos, estructurales, hidráulicos o eléctricos).

                Analiza la(s) imagen(es) adjuntas de un plano y devuelve SOLO un JSON (sin texto
                adicional, sin markdown) con esta forma:

                {
                  "tipo_plano": "arquitectonico|estructural|hidraulico|electrico|otro",
                  "proyecto_detectado": "nombre del proyecto si aparece en el rótulo, o null",
                  "cotas": [{"elemento": "descripción de qué mide", "valor": "ej. 7.91 m"}],
                  "profundidades": ["0.30 m", "1.10 m", "1.40 m"],
                  "materiales_o_equipos_mencionados": ["lista de materiales, equipos o partidas
                    que aparezcan nombrados en el plano o su leyenda"],
                  "notas": "cualquier advertencia sobre cotas ilegibles, ambiguas o que no se
                    pudieron leer con confianza"
                }

                Si una imagen no aparenta ser un plano de construcción, dilo en "notas" y deja
                los demás campos vacíos. No inventes cotas que no veas con claridad.
                PROMPT,
        ]];

        foreach (array_slice($imagenes, 0, 5) as $imagePath) {
            $content[] = [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => 'image/jpeg',
                    'data' => base64_encode(file_get_contents($imagePath)),
                ],
            ];
        }

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => self::MODEL,
            'max_tokens' => 2000,
            'messages' => [['role' => 'user', 'content' => $content]],
        ]);

        if ($response->failed()) {
            Log::error('PlanoAnalyzerService: fallo la llamada a Anthropic', ['body' => $response->body()]);
            throw new \RuntimeException('La API de Anthropic devolvió un error al analizar el plano.');
        }

        $texto = collect($response->json('content', []))
            ->firstWhere('type', 'text')['text'] ?? '{}';

        $texto = preg_replace('/^```json|```$/m', '', trim($texto));

        $json = json_decode($texto, true);

        return is_array($json) ? $json : ['notas' => 'No se pudo interpretar la respuesta de la IA.', 'raw' => $texto];
    }
}
