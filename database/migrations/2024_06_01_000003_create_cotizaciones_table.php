<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->string('numero')->nullable();
            $table->unsignedInteger('jornales')->default(63);
            $table->decimal('factor_prestacional', 5, 4)->default(1.5200);
            $table->decimal('imprevistos_mano_obra_pct', 5, 4)->default(0.10);
            $table->decimal('transporte_pct', 5, 4)->default(0.10);
            $table->decimal('herramienta_pct', 5, 4)->default(0.03);
            $table->decimal('administracion_pct', 5, 4)->default(0.08);
            $table->decimal('imprevistos_pct', 5, 4)->default(0.07);
            $table->decimal('utilidad_pct', 5, 4)->default(0.10);
            $table->decimal('iva_pct', 5, 4)->default(0.19);
            $table->enum('estado', ['borrador', 'enviada', 'aprobada', 'rechazada'])->default('borrador');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
