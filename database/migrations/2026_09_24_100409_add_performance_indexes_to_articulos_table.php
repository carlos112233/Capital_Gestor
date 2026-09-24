<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega índices de rendimiento para acelerar:
     * - Búsqueda de artículos por nombre (LIKE)
     * - Filtrado por disponible
     * - Búsqueda de ventas por fecha
     * - Búsqueda de entradas por fecha y estado
     */
    public function up(): void
    {
        // Índice en articulos.nombre para búsquedas LIKE 'prefix%' y ORDER BY
        Schema::table('articulos', function (Blueprint $table) {
            if (!$this->indexExists('articulos', 'articulos_nombre_index')) {
                $table->index('nombre', 'articulos_nombre_index');
            }
            if (!$this->indexExists('articulos', 'articulos_disponible_index')) {
                $table->index('disponible', 'articulos_disponible_index');
            }
        });

        // Índice en ventas.created_at para filtros por período
        Schema::table('ventas', function (Blueprint $table) {
            if (!$this->indexExists('ventas', 'ventas_created_at_index')) {
                $table->index('created_at', 'ventas_created_at_index');
            }
        });

        // Índices en entradas para filtros por fecha y cliente
        Schema::table('entradas', function (Blueprint $table) {
            if (!$this->indexExists('entradas', 'entradas_fecha_generado_index')) {
                $table->index('fecha_generado', 'entradas_fecha_generado_index');
            }
            if (!$this->indexExists('entradas', 'entradas_created_at_index')) {
                $table->index('created_at', 'entradas_created_at_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropIndexIfExists('articulos_nombre_index');
            $table->dropIndexIfExists('articulos_disponible_index');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndexIfExists('ventas_created_at_index');
        });

        Schema::table('entradas', function (Blueprint $table) {
            $table->dropIndexIfExists('entradas_fecha_generado_index');
            $table->dropIndexIfExists('entradas_created_at_index');
        });
    }

    /**
     * Verifica si un índice ya existe antes de crearlo (evita errores en re-ejecución).
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $dbName = $connection->getDatabaseName();

        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$dbName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
