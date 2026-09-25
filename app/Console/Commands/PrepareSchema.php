<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * `php artisan migrate` crea su propia tabla de control `migrations` (usando el
 * search_path configurado) ANTES de leer ningún archivo de migración. Si el
 * schema del proyecto todavía no existe la primera vez que se despliega, esa
 * creación falla. Este comando crea el schema de forma standalone y debe
 * correrse ANTES de `migrate` en el entrypoint del deploy (ver docker/start.sh).
 *
 * Es idempotente (CREATE SCHEMA IF NOT EXISTS): correrlo de nuevo en despliegues
 * posteriores no hace nada.
 */
class PrepareSchema extends Command
{
    protected $signature = 'db:prepare-schema';

    protected $description = 'Crea el schema de Postgres del proyecto si no existe (correr antes de migrate)';

    public function handle(): int
    {
        $connection = config('database.default');
        $schema = config("database.connections.{$connection}.app_schema", 'public');

        if ($connection !== 'pgsql' || $schema === 'public') {
            $this->info('Nada que preparar (conexión no es pgsql, o el schema es "public").');

            return self::SUCCESS;
        }

        DB::statement('CREATE SCHEMA IF NOT EXISTS "'.str_replace('"', '', $schema).'"');
        $this->info("Schema \"{$schema}\" listo.");

        return self::SUCCESS;
    }
}
