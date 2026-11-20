<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->string('banco')->nullable()->after('imagen');
            $table->string('clave_rastreo')->nullable()->after('banco');
            $table->string('fecha_transferencia')->nullable()->after('clave_rastreo');
            $table->string('clabe_cuenta')->nullable()->after('fecha_transferencia');
            $table->decimal('monto_extraido', 10, 2)->nullable()->after('clabe_cuenta');
            $table->text('datos_ocr_json')->nullable()->after('monto_extraido');
        });

        // Modificar columna status para permitir 'procesando_pago'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE comprobantes MODIFY COLUMN status ENUM('procesando_pago', 'pendiente', 'aprobado', 'rechazado') DEFAULT 'procesando_pago'");
        } else {
            Schema::table('comprobantes', function (Blueprint $table) {
                $table->enum('status', ['procesando_pago', 'pendiente', 'aprobado', 'rechazado'])
                    ->default('procesando_pago')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn([
                'banco',
                'clave_rastreo',
                'fecha_transferencia',
                'clabe_cuenta',
                'monto_extraido',
                'datos_ocr_json',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE comprobantes MODIFY COLUMN status ENUM('pendiente', 'aprobado', 'rechazado') DEFAULT 'pendiente'");
        }
    }
};
