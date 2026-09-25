<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->enum('categoria', ['obra_civil', 'hidraulico', 'electrico']);
            $table->string('descripcion');
            $table->string('unidad', 20);
            $table->decimal('cantidad', 12, 3);
            $table->decimal('valor_unitario', 14, 2);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_items');
    }
};
