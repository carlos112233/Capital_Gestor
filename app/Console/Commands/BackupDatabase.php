<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--days=7 : Número de días a retener backups de la base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar respaldo completo de la base de datos en archivo comprimido .sql.gz y eliminar respaldos antiguos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Iniciando respaldo de la base de datos...');

        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', 3306);
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        if (!$database) {
            $this->error('No se ha configurado el nombre de la base de datos.');
            Log::error('db:backup error: Base de datos no configurada en MySQL.');
            return Command::FAILURE;
        }

        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$database}_{$timestamp}.sql.gz";
        $filepath = $backupDir . '/' . $filename;

        // Escapar variables y ejecutar mysqldump vía MYSQL_PWD por seguridad
        $cmd = sprintf(
            'MYSQL_PWD=%s mysqldump --no-tablespaces -h %s -P %s -u %s %s | gzip > %s',
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $returnVar = 0;
        $output = [];
        exec($cmd, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
            $this->error("Error al generar el respaldo de la base de datos: {$database}");
            Log::error("db:backup falló para la base de datos {$database}.");
            return Command::FAILURE;
        }

        $sizeMb = round(filesize($filepath) / 1024 / 1024, 2);
        $this->info("Respaldo creado exitosamente: {$filename} ({$sizeMb} MB)");
        Log::info("Respaldo de base de datos exitoso: {$filename} ({$sizeMb} MB)");

        // Limpiar respaldos anteriores a --days
        $days = (int) $this->option('days');
        if ($days > 0) {
            $threshold = now()->subDays($days)->getTimestamp();
            $files = glob($backupDir . '/backup_*.sql.gz');
            $deletedCount = 0;

            if (is_array($files)) {
                foreach ($files as $file) {
                    if (is_file($file) && filemtime($file) < $threshold) {
                        @unlink($file);
                        $deletedCount++;
                    }
                }
            }

            if ($deletedCount > 0) {
                $this->info("Se eliminaron {$deletedCount} respaldo(s) con antigüedad mayor a {$days} días.");
                Log::info("db:backup: eliminados {$deletedCount} respaldos antiguos.");
            }
        }

        return Command::SUCCESS;
    }
}
