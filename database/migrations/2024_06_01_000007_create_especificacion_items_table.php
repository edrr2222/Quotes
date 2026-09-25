<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especificacion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            // Texto libre, no enum: la especificación no siempre calza en obra_civil/hidraulico/
            // electrico (a veces es "Demolición", "Localización y trazado", etc.). Si coincide
            // con una categoría de materiales, se usa el mismo texto para que quede agrupado
            // visualmente igual en los PDF, pero no se fuerza.
            $table->string('categoria')->nullable();
            $table->text('descripcion');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especificacion_items');
    }
};
