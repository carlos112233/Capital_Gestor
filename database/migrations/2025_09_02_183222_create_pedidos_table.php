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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');   // usuario que hace el pedido
            $table->foreignId('articulo_id')->constrained()->onDelete('cascade'); // artículo vendido
            $table->string('descripcion')->nullable();
            $table->decimal('costo', 10, 2);
            $table->foreignId('venta_id')->nullable()->constrained()->onDelete('set null'); // relación con la venta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
