Agrega este bloque a `config/services.php` (en el array que retorna el archivo):

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],
