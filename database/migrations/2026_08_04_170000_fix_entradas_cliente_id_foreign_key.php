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
        if (Schema::hasTable('entradas') && Schema::hasColumn('entradas', 'cliente_id')) {
            // Eliminar la Foreign Key incorrecta que apuntaba a la tabla inexistente 'clientes'
            try {
                Schema::table('entradas', function (Blueprint $table) {
                    $table->dropForeign('entradas_cliente_id_foreign');
                });
            } catch (\Exception $e) {
                // Si la FK no existe o tiene otro nombre en la BD, se ignora silenciosamente
            }

            // Apuntar cliente_id a la tabla 'users'
            try {
                Schema::table('entradas', function (Blueprint $table) {
                    $table->foreign('cliente_id')->references('id')->on('users')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Si ya existe la relación a 'users', continuar sin error
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('entradas') && Schema::hasColumn('entradas', 'cliente_id')) {
            try {
                Schema::table('entradas', function (Blueprint $table) {
                    $table->dropForeign(['cliente_id']);
                });
            } catch (\Exception $e) {
                // Ignorar
            }
        }
    }
};
