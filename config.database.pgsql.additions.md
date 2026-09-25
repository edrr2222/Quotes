Reemplaza el array `'pgsql' => [...]` que trae por defecto `config/database.php`
(dentro de `connections`) por este bloque. No toques las otras conexiones
(sqlite, mysql, etc.) que estén en el mismo archivo.

    'pgsql' => [
        'driver' => 'pgsql',
        'url' => env('DATABASE_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '5432'),
        'database' => env('DB_DATABASE', 'laravel'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8',
        'prefix' => '',
        'prefix_indexes' => true,

        // Nombre "limpio" del schema propio (sin ", public"). Es una clave
        // nuestra, no nativa de Laravel — la usa el comando db:prepare-schema
        // para no tener que volver a parsear el search_path.
        'app_schema' => env('DB_SCHEMA', 'public'),

        // Clave nativa de Laravel: arma el `search_path` real de la conexión.
        // Acepta array, así que no hay que concatenar strings a mano. En local
        // (DB_SCHEMA sin definir => 'public') queda igual que un Laravel normal.
        'schema' => env('DB_SCHEMA', 'public') === 'public'
            ? 'public'
            : [env('DB_SCHEMA'), 'public'],

        'sslmode' => env('DB_SSLMODE', 'prefer'),
    ],

Por qué dos claves separadas ('app_schema' y 'schema'): 'schema' es la que Laravel
lee para construir el `search_path` real de la conexión (puede terminar siendo
"cotizador, public"); 'app_schema' es SIEMPRE el nombre limpio del schema solo
("cotizador"), sin importar el fallback — es la que usan db:prepare-schema y
cualquier otro sitio del código que necesite el nombre exacto del schema (por
ejemplo, para armar una ruta de storage por schema, o un log).
