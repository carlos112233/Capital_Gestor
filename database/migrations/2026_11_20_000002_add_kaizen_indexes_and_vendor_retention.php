<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mejora Kaizen #1 y #4:
     * 1. Índice B-Tree compuesto (status, created_at) en whatsapp_pending_messages (Cierre DT-09).
     * 2. Índice B-Tree compuesto (user_id, status) en comprobantes.
     * 3. Columnas porcentaje_retencion en users y retencion_default en app_settings con backfill desde JSON si existe.
     */
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_pending_messages')) {
            Schema::table('whatsapp_pending_messages', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_wa_pending_status_created');
            });
        }

        if (Schema::hasTable('comprobantes')) {
            Schema::table('comprobantes', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_comprobantes_user_status');
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'porcentaje_retencion')) {
            Schema::table('users', function (Blueprint $table) {
                $table->decimal('porcentaje_retencion', 5, 2)->nullable()->default(null)->after('theme_mode');
            });
        }

        if (Schema::hasTable('app_settings') && !Schema::hasColumn('app_settings', 'retencion_default')) {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->decimal('retencion_default', 5, 2)->default(20.00)->after('theme_mode');
            });
        }

        // Backfill idempotente desde storage/app/retencion_vendedores.json si existe en disco
        $jsonPath = storage_path('app/retencion_vendedores.json');
        if (is_file($jsonPath)) {
            $decoded = json_decode((string) file_get_contents($jsonPath), true);
            if (is_array($decoded)) {
                if (isset($decoded['default']) && is_numeric($decoded['default']) && Schema::hasTable('app_settings')) {
                    DB::table('app_settings')->update([
                        'retencion_default' => round((float) $decoded['default'], 2),
                    ]);
                }

                if (!empty($decoded['vendedores']) && is_array($decoded['vendedores']) && Schema::hasTable('users')) {
                    foreach ($decoded['vendedores'] as $vendedorId => $pct) {
                        if (is_numeric($vendedorId) && is_numeric($pct)) {
                            DB::table('users')
                                ->where('id', (int) $vendedorId)
                                ->update(['porcentaje_retencion' => round((float) $pct, 2)]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('whatsapp_pending_messages')) {
            Schema::table('whatsapp_pending_messages', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_wa_pending_status_created');
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('comprobantes')) {
            Schema::table('comprobantes', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_comprobantes_user_status');
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'porcentaje_retencion')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('porcentaje_retencion');
            });
        }

        if (Schema::hasTable('app_settings') && Schema::hasColumn('app_settings', 'retencion_default')) {
            Schema::table('app_settings', function (Blueprint $table) {
                $table->dropColumn('retencion_default');
            });
        }
    }
};
