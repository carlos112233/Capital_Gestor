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
        if (!Schema::hasTable('feedbacks')) {
            Schema::create('feedbacks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('tipo')->default('sugerencia'); // 'queja', 'comentario', 'sugerencia'
                $table->string('asunto')->nullable();
                $table->text('mensaje');
                $table->longText('imagen')->nullable(); // Imagen o base64
                $table->string('estatus')->default('enviado'); // 'enviado' (rojo), 'leyendo' (naranja), 'leido' (verde)
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('feedback_mensajes')) {
            Schema::create('feedback_mensajes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('feedback_id')->constrained('feedbacks')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('mensaje');
                $table->longText('imagen')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_mensajes');
        Schema::dropIfExists('feedbacks');
    }
};
