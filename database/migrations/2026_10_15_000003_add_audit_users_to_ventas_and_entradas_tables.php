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
     */
    public function up(): void
    {
        // 1. Agregar columna de trazabilidad en ventas (quién operó/realizó la compra físicamente)
        Schema::table('ventas', function (Blueprint $table): void {
            $table->unsignedBigInteger('comprador_id')->nullable()->after('user_id');
            $table->foreign('comprador_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // 2. Backfill seguro en ventas: los registros previos asumen comprador_id = user_id
        DB::table('ventas')
            ->whereNull('comprador_id')
            ->update(['comprador_id' => DB::raw('user_id')]);

        // 3. Agregar columna de trazabilidad en entradas (quién registró la entrada/abono en el sistema)
        Schema::table('entradas', function (Blueprint $table): void {
            $table->unsignedBigInteger('registrado_por_id')->nullable()->after('cliente_id');
            $table->foreign('registrado_por_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // 4. Backfill seguro en entradas: registros históricos heredan cliente_id o user_id
        DB::table('entradas')
            ->whereNull('registrado_por_id')
            ->update(['registrado_por_id' => DB::raw('COALESCE(cliente_id, user_id, 1)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table): void {
            $table->dropForeign(['comprador_id']);
            $table->dropColumn('comprador_id');
        });

        Schema::table('entradas', function (Blueprint $table): void {
            $table->dropForeign(['registrado_por_id']);
            $table->dropColumn('registrado_por_id');
        });
    }
};

