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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('score_calculado')->default(70)->after('telefono');
            $table->integer('score_manual')->nullable()->after('score_calculado');
            $table->boolean('override_score')->default(false)->after('score_manual');
            $table->text('notas_scoring')->nullable()->after('override_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'score_calculado',
                'score_manual',
                'override_score',
                'notas_scoring',
            ]);
        });
    }
};
