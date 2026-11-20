<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade índices compuestos B-Tree en la tabla pedidos:
     * - idx_pedidos_user_created (['user_id', 'created_at']) para scoring y estados de cuenta por cliente.
     * - idx_pedidos_status_created (['status', 'created_at']) para alertas matutinas y filtrado operativo.
     */
    public function up(): void
    {
        if (Schema::hasTable('pedidos')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_pedidos_user_created');
                $table->index(['status', 'created_at'], 'idx_pedidos_status_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pedidos')) {
            Schema::table('pedidos', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_pedidos_user_created');
                } catch (\Throwable $e) {
                }

                try {
                    $table->dropIndex('idx_pedidos_status_created');
                } catch (\Throwable $e) {
                }
            });
        }
    }
};
