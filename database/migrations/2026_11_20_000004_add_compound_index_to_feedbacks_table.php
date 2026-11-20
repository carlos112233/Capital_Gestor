<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade índice B-Tree compuesto (user_id, estatus) en la tabla feedbacks
     * para optimizar el listado y conteos por estatus en FeedbackController@index.
     */
    public function up(): void
    {
        if (Schema::hasTable('feedbacks')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->index(['user_id', 'estatus'], 'idx_feedbacks_user_estatus');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('feedbacks')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                try {
                    $table->dropIndex('idx_feedbacks_user_estatus');
                } catch (\Throwable $e) {
                }
            });
        }
    }
};
