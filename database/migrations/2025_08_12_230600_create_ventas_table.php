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
    Schema::create('ventas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->comment('Usuario que realizó la venta');
        $table->foreignId('articulo_id')->constrained()->comment('Artículo vendido'); // Enlace al usuario
        $table->decimal('precio_venta', 10, 2)->comment('Precio al momento de la venta');
        $table->decimal('total_venta', 10, 2)->comment('Total (cantidad * precio_venta)');
        $table->text('descripcion')->nullable();
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
