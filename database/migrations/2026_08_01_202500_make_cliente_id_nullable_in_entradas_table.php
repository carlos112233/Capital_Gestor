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
        if (Schema::hasColumn('entradas', 'cliente_id')) {
            DB::statement('ALTER TABLE entradas DROP CONSTRAINT IF EXISTS entradas_cliente_id_foreign;');
            DB::statement('ALTER TABLE entradas ALTER COLUMN cliente_id DROP NOT NULL;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('entradas', 'cliente_id')) {
            DB::statement('ALTER TABLE entradas ALTER COLUMN cliente_id SET NOT NULL;');
        }
    }
};
