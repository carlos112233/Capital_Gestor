<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega índices B-Tree compuestos optimizados para soportar consultas de alto rendimiento,
     * eliminando operaciones de 'Using filesort' y permitiendo 'Index-Only Scan' (Covering Index)
     * en reportes financieros y cortes de caja por período y usuario/cliente.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Índice B-Tree compuesto para:
            // 1. Filtrado exacto por user_id (Leading column, cardinalidad alta)
            // 2. Rango y ordenamiento cronológico sin filesort por created_at (Range/Order column)
            // 3. Proyección y agregación SUM(total_venta) directa desde el árbol B-Tree (Covering column)
            if (!$this->indexExists('ventas', 'ventas_user_id_created_at_total_venta_index')) {
                $table->index(
                    ['user_id', 'created_at', 'total_venta'],
                    'ventas_user_id_created_at_total_venta_index'
                );
            }
        });

        Schema::table('entradas', function (Blueprint $table) {
            // Índice B-Tree compuesto para:
            // 1. Filtrado por cliente_id (Leading column)
            // 2. Rango cronológico por created_at (Range/Order column)
            // 3. Proyección directa y cálculo de saldos por precio_venta (Covering column)
            if (!$this->indexExists('entradas', 'entradas_cliente_id_created_at_precio_venta_index')) {
                $table->index(
                    ['cliente_id', 'created_at', 'precio_venta'],
                    'entradas_cliente_id_created_at_precio_venta_index'
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * Elimina quirúrgicamente los índices compuestos.
     * En MySQL InnoDB, si el índice compuesto era el único que respaldaba la Foreign Key,
     * MySQL bloquea el drop con 'Error 1553: needed in a foreign key constraint'.
     * Por ello, se restaura preventivamente el índice individual de la FK antes del dropIndex.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (!$this->indexExists('ventas', 'ventas_user_id_foreign')) {
                $table->index('user_id', 'ventas_user_id_foreign');
            }
            if ($this->indexExists('ventas', 'ventas_user_id_created_at_total_venta_index')) {
                $table->dropIndex('ventas_user_id_created_at_total_venta_index');
            }
        });

        Schema::table('entradas', function (Blueprint $table) {
            if (!$this->indexExists('entradas', 'entradas_cliente_id_foreign')) {
                $table->index('cliente_id', 'entradas_cliente_id_foreign');
            }
            if ($this->indexExists('entradas', 'entradas_cliente_id_created_at_precio_venta_index')) {
                $table->dropIndex('entradas_cliente_id_created_at_precio_venta_index');
            }
        });
    }

    /**
     * Verifica la existencia de un índice en information_schema de forma segura y portable.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $count = DB::table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->count();

        return $count > 0;
    }
};

