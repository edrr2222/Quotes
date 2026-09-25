<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained()->cascadeOnDelete();
            // Los 3 documentos que debe poder generar el sistema (ver CLAUDE.md):
            //  - especificacion:            solo el alcance/especificación del trabajo, sin precios
            //  - especificacion_mano_obra:  especificación + cotización de mano de obra
            //  - especificacion_materiales: especificación + cotización de materiales
            $table->enum('tipo', ['especificacion', 'especificacion_mano_obra', 'especificacion_materiales']);
            $table->string('ruta_archivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
