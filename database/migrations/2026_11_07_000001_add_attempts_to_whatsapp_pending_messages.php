<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_pending_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_pending_messages', 'attempts')) {
                $table->unsignedSmallInteger('attempts')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_pending_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_pending_messages', 'attempts')) {
                $table->dropColumn('attempts');
            }
        });
    }
};
