<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta_archivo');
            $table->enum('tipo', ['arquitectonico', 'estructural', 'hidraulico', 'electrico', 'otro'])
                ->default('otro');
            $table->enum('estado_analisis', ['pendiente', 'procesando', 'listo', 'error'])
                ->default('pendiente');
            $table->json('analisis_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planos');
    }
};
