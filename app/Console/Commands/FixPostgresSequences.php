<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class FixPostgresSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
  protected $signature = 'db:fix-sequences';

    protected $description = 'Sincroniza las secuencias de IDs en PostgreSQL para evitar errores de clave duplicada';

    public function handle()
    {
        $this->info('Iniciando sincronización de secuencias...');

        // Buscamos solo tablas que tengan una columna llamada 'id' en el esquema public
        $tables = DB::select("
            SELECT table_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND column_name = 'id'
        ");

        foreach ($tables as $table) {
            $tableName = $table->table_name;

            try {
                // Intentamos obtener el nombre de la secuencia
                $seq = DB::selectOne("SELECT pg_get_serial_sequence('\"$tableName\"', 'id') as seqname");

                if ($seq && $seq->seqname) {
                    // Reseteamos la secuencia al valor máximo del ID + 1
                    DB::statement("SELECT setval('{$seq->seqname}', coalesce(max(id), 0) + 1, false) FROM \"$tableName\"");
                    $this->line("✅ Secuencia corregida: <info>$tableName</info>");
                }
            } catch (\Exception $e) {
                // Si una tabla falla, la saltamos y seguimos con las demás
                $this->warn("⚠️  Saltando tabla '$tableName': " . $e->getMessage());
            }
        }

        $this->info('¡Proceso finalizado!');
        return 0;
    }
}
