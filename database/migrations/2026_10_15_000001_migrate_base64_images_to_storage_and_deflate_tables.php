<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Transfiere cadenas Base64 desde LONGTEXT en MySQL hacia storage/app/public/ (disco 'public'),
     * reduciendo las columnas a VARCHAR(255) y ejecutando OPTIMIZE TABLE para defragmentar
     * las páginas overflow de InnoDB y desinflar el Buffer Pool.
     */
    public function up(): void
    {
        // Ejecución quirúrgica a través del comando Artisan auditado
        Artisan::call('storage:migrate-base64');
    }

    /**
     * Reverse the migrations.
     *
     * Re-expande columnas a LONGTEXT, lee los archivos físicos en disco y
     * re-codifica el contenido binario a Base64 restaurando el estado original.
     */
    public function down(): void
    {
        Artisan::call('storage:migrate-base64', ['--rollback' => true]);
    }
};

