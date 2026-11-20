<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade metadatos estructurados de cocina a la tabla articulos:
     * - requiere_cocina (boolean, default true)
     * - categoria_cocina (string 40, nullable, default 'otro': 'cemita', 'milanesa_pollo', 'milanesa_puerco', 'bebida', 'otro')
     * - Índice B-Tree compuesto (disponible, requiere_cocina) para filtrado en POS y despacho a cocina.
     */
    public function up(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            if (!Schema::hasColumn('articulos', 'requiere_cocina')) {
                $table->boolean('requiere_cocina')->default(true)->after('disponible');
            }

            if (!Schema::hasColumn('articulos', 'categoria_cocina')) {
                $table->string('categoria_cocina', 40)->nullable()->default('otro')->after('requiere_cocina');
            }

            $table->index(['disponible', 'requiere_cocina'], 'idx_articulos_disp_cocina');
        });

        // Backfill idempotente de artículos existentes
        $articulos = DB::table('articulos')->select(['id', 'nombre', 'descripcion'])->get();

        foreach ($articulos as $art) {
            $nombreLower = mb_strtolower(trim((string) ($art->nombre ?? '')));
            $textoCompleto = mb_strtolower(trim($nombreLower . ' ' . (string) ($art->descripcion ?? '')));

            $requiereCocina = true;
            $categoriaCocina = 'otro';

            if (in_array($nombreLower, ['pago saldado', 'abono', 'saldo'], true)) {
                $requiereCocina = false;
                $categoriaCocina = 'otro';
            } elseif (
                str_contains($textoCompleto, 'refresco')
                || str_contains($textoCompleto, 'coca')
                || str_contains($textoCompleto, 'agua')
                || str_contains($textoCompleto, 'jugo')
                || str_contains($textoCompleto, 'bebida')
            ) {
                $requiereCocina = false;
                $categoriaCocina = 'bebida';
            } elseif (str_contains($textoCompleto, 'cemita') || str_contains($textoCompleto, 'milanesa')) {
                $requiereCocina = true;
                if (
                    str_contains($textoCompleto, 'hawaiana')
                    || str_contains($textoCompleto, 'cubana')
                    || str_contains($textoCompleto, 'texana')
                    || str_contains($textoCompleto, 'tejama')
                ) {
                    $categoriaCocina = 'cemita';
                } elseif (str_contains($textoCompleto, 'pollo')) {
                    $categoriaCocina = 'milanesa_pollo';
                } elseif (
                    str_contains($textoCompleto, 'puerco')
                    || str_contains($textoCompleto, 'cerdo')
                    || str_contains($textoCompleto, 'milanesa')
                ) {
                    $categoriaCocina = 'milanesa_puerco';
                } else {
                    $categoriaCocina = 'cemita';
                }
            }

            DB::table('articulos')
                ->where('id', $art->id)
                ->update([
                    'requiere_cocina'  => $requiereCocina,
                    'categoria_cocina' => $categoriaCocina,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_articulos_disp_cocina');
            } catch (\Throwable $e) {
                // Ignorar si el índice no existe
            }

            $columnsToDrop = [];
            if (Schema::hasColumn('articulos', 'categoria_cocina')) {
                $columnsToDrop[] = 'categoria_cocina';
            }
            if (Schema::hasColumn('articulos', 'requiere_cocina')) {
                $columnsToDrop[] = 'requiere_cocina';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
