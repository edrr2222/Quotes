<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nombre');
            $table->string('cliente')->nullable();
            $table->string('municipio')->nullable();
            $table->string('departamento')->nullable();
            $table->text('descripcion')->nullable();
            $table->enum('estado', ['borrador', 'en_cotizacion', 'cotizado', 'aprobado', 'archivado'])
                ->default('borrador');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
