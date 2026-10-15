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
        // 1. Registro idempotente de los 4 roles oficiales y saneamiento de rol 'user'
        if (Schema::hasTable('roles')) {
            DB::table('roles')->updateOrInsert(
                ['name' => 'admin'],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('roles')->updateOrInsert(
                ['name' => 'vendedor'],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('roles')->updateOrInsert(
                ['name' => 'cocina'],
                ['created_at' => now(), 'updated_at' => now()]
            );
            DB::table('roles')->updateOrInsert(
                ['name' => 'cliente'],
                ['created_at' => now(), 'updated_at' => now()]
            );

            // Migrar asignaciones de 'user' a 'cliente' y sanear rol 'user'
            $userRole = DB::table('roles')->where('name', 'user')->first();
            $clienteRole = DB::table('roles')->where('name', 'cliente')->first();
            if ($userRole && $clienteRole) {
                $userIdsWithUserRole = DB::table('role_user')->where('role_id', $userRole->id)->pluck('user_id');
                foreach ($userIdsWithUserRole as $uId) {
                    $exists = DB::table('role_user')->where('user_id', $uId)->where('role_id', $clienteRole->id)->exists();
                    if ($exists) {
                        DB::table('role_user')->where('user_id', $uId)->where('role_id', $userRole->id)->delete();
                    } else {
                        DB::table('role_user')->where('user_id', $uId)->where('role_id', $userRole->id)->update(['role_id' => $clienteRole->id]);
                    }
                }

                $remaining = DB::table('role_user')->where('role_id', $userRole->id)->count();
                if ($remaining === 0) {
                    DB::table('roles')->where('id', $userRole->id)->delete();
                }
            }
        }

        // 2. Creación de la tabla pagos_vendedores
        Schema::create('pagos_vendedores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vendedor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('registrado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->decimal('total_vendido', 10, 2);
            $table->decimal('porcentaje_retencion', 5, 2)->default(20.00);
            $table->decimal('monto_retenido', 10, 2);
            $table->decimal('monto_neto_pagado', 10, 2);
            $table->string('metodo_pago', 50)->default('efectivo');
            $table->text('notas')->nullable();
            $table->timestamps();

            // Índice compuesto para auditoría y consultas cronológicas por vendedor
            $table->index(['vendedor_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_vendedores');
    }
};

