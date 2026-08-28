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
        Schema::table('whatsapp_pending_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_pending_messages', 'pdf_path')) {
                $table->string('pdf_path', 500)->nullable()->after('mensaje');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_pending_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_pending_messages', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });
    }
};
