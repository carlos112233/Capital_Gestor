<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Enlace al usuario
            $table->foreignId('cliente_id')->constrained()->comment('cliente'); // Enlace al usuario
            $table->foreignId('articulo_id')->constrained()->comment('Artículo vendido'); // Enlace al usuario
            $table->decimal('monto', 10, 2); // Monto con 2 decimales
            $table->text('descripcion')->nullable(); // Una descripción opcional
            $table->date('fecha_generado'); // La fecha en que se generó el ingreso
            $table->timestamps(); // created_at y updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas');
    }
};
