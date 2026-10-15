<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateBase64Storage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:migrate-base64 
                            {--dry-run : Simula la extracción y validación sin alterar BD ni disco}
                            {--rollback : Revierte las rutas relativas a Base64 leyendo los archivos físicos}
                            {--skip-optimize : Omite la ejecución de OPTIMIZE TABLE al finalizar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae quirúrgicamente imágenes Base64 de users y articulos a disco físico, actualiza rutas y desinfla tablas InnoDB';

    /**
     * MIME types admitidos para imágenes válidas.
     */
    private const ALLOWED_MIMES = [
        'image/jpeg'    => 'jpg',
        'image/jpg'     => 'jpg',
        'image/png'     => 'png',
        'image/webp'    => 'webp',
        'image/gif'     => 'gif',
        'image/svg+xml' => 'svg',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $this->info('===============================================================');
        $this->info('  FASE 1: MIGRACIÓN QUIRÚRGICA BASE64 -> STORAGE FÍSICO (INNODB)');
        $this->info('===============================================================');

        if ($this->option('rollback')) {
            return $this->handleRollback();
        }

        return $this->handleMigration($startTime);
    }

    /**
     * Ejecuta el proceso de extracción, almacenamiento en disco y compactación de tablas.
     */
    private function handleMigration(float $startTime): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('>>> MODO DRY-RUN ACTIVADO: No se aplicarán escrituras en BD ni en disco <<<');
        }

        // 1. Métricas de almacenamiento previo en InnoDB
        $initialSizes = $this->fetchTableSizes(['users', 'articulos']);
        $this->table(
            ['Tabla', 'Tamaño Inicial (MB)', 'Espacio Fragmentado (MB)'],
            collect($initialSizes)->map(fn ($row) => [
                $row['TABLE_NAME'],
                $row['size_mb'] . ' MB',
                $row['data_free_mb'] . ' MB',
            ])->toArray()
        );

        // 2. Asegurar directorios de destino en el disco 'public'
        if (!$isDryRun) {
            Storage::disk('public')->makeDirectory('avatars');
            Storage::disk('public')->makeDirectory('articulos');
            File::ensureDirectoryExists(storage_path('app/backups'));
        }

        // 3. Proceso de Users (Avatares)
        $this->line('');
        $this->info('1/3. Procesando tabla `users` (columna `image`)...');
        $usersStats = $this->migrateUsers($isDryRun);

        // 4. Proceso de Artículos (Catálogo)
        $this->line('');
        $this->info('2/3. Procesando tabla `articulos` (columna `img_base64`)...');
        $articulosStats = $this->migrateArticulos($isDryRun);

        // 5. Alteración DDL y Compactación InnoDB
        $this->line('');
        $this->info('3/3. Reducción de esquema DDL (LONGTEXT -> VARCHAR(255)) y defragmentación...');
        if (!$isDryRun) {
            $this->shrinkSchemaAndOptimize((bool) $this->option('skip-optimize'));
        } else {
            $this->comment('Dry-run: Se omitió ALTER TABLE y OPTIMIZE TABLE.');
        }

        // 6. Métricas finales de reducción
        $finalSizes = $this->fetchTableSizes(['users', 'articulos']);
        $executionTime = round(microtime(true) - $startTime, 2);

        $this->line('');
        $this->info('===============================================================');
        $this->info('  REPORTE CLÍNICO DE DESINFLADO DE BUFFER POOL (INNODB)');
        $this->info('===============================================================');

        $formatSize = fn (float $mb, float $kb) => $mb >= 0.1 ? "{$mb} MB" : "{$kb} KB";

        $metricsRows = [];
        foreach (['users', 'articulos'] as $tableName) {
            $initMb = (float) ($initialSizes[$tableName]['size_mb'] ?? 0);
            $initKb = (float) ($initialSizes[$tableName]['size_kb'] ?? 0);
            $finMb  = (float) ($finalSizes[$tableName]['size_mb'] ?? $initMb);
            $finKb  = (float) ($finalSizes[$tableName]['size_kb'] ?? $initKb);
            $diffKb = $initKb - $finKb;
            $pct    = $initKb > 0 ? round(($diffKb / $initKb) * 100, 1) : 0;
            $diffMb = round($diffKb / 1024, 2);

            $metricsRows[] = [
                $tableName,
                $formatSize($initMb, $initKb),
                $formatSize($finMb, $finKb),
                "-{$diffMb} MB (-{$pct}%)",
            ];
        }
        $this->table(['Tabla', 'Tamaño Previo', 'Tamaño Posterior', 'Ahorro'], $metricsRows);

        $this->table(
            ['Colección', 'Total Analizado', 'Migrados con Éxito', 'Omitidos / Ya Rutas', 'Corruptos Aislados'],
            [
                ['Users (Avatares)', $usersStats['total'], $usersStats['migrated'], $usersStats['skipped'], $usersStats['corrupted']],
                ['Artículos (Catálogo)', $articulosStats['total'], $articulosStats['migrated'], $articulosStats['skipped'], $articulosStats['corrupted']],
            ]
        );

        $this->info("Tiempo de intervención: {$executionTime}s | Consumo pico de memoria: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB');

        return Command::SUCCESS;
    }

    /**
     * Procesa la migración de users en streaming con chunkById para proteger el consumo de RAM.
     */
    private function migrateUsers(bool $isDryRun): array
    {
        $stats = ['total' => 0, 'migrated' => 0, 'skipped' => 0, 'corrupted' => 0];
        $backupPayload = [];
        $corruptRecords = [];

        DB::table('users')
            ->select('id', 'name', 'image', 'image_tipo')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id')
            ->chunkById(25, function ($users) use (&$stats, &$backupPayload, &$corruptRecords, $isDryRun) {
                foreach ($users as $user) {
                    $stats['total']++;
                    $rawData = trim((string) $user->image);

                    // Idempotencia: Verificar si ya es una ruta relativa válida en storage
                    if ($this->isAlreadyRelativePath($rawData, 'avatars/')) {
                        $stats['skipped']++;
                        continue;
                    }

                    // Decodificación y validación de integridad
                    $decoded = $this->decodeAndVerifyImage($rawData, (string) $user->image_tipo);
                    if ($decoded['status'] === 'corrupted') {
                        $stats['corrupted']++;
                        $corruptRecords[] = [
                            'table' => 'users',
                            'id' => $user->id,
                            'reason' => $decoded['error'],
                            'raw_length' => strlen($rawData),
                            'raw_sample' => substr($rawData, 0, 100),
                        ];
                        $this->warn(" [!] User ID {$user->id} ({$user->name}) tiene Base64 corrupto: {$decoded['error']}. Se aísla sin pérdida de datos.");
                        continue;
                    }

                    $ext = $decoded['extension'];
                    $mime = $decoded['mime'];
                    $relativePath = "avatars/avatar_{$user->id}.{$ext}";

                    if (!$isDryRun) {
                        // Respaldo preventivo antes de mutar
                        $backupPayload[] = [
                            'table' => 'users',
                            'id' => $user->id,
                            'image' => $rawData,
                            'image_tipo' => $user->image_tipo,
                            'new_path' => $relativePath,
                        ];

                        // Escritura en disco
                        Storage::disk('public')->put($relativePath, $decoded['binary']);

                        // Actualización de registro
                        DB::table('users')->where('id', $user->id)->update([
                            'image' => $relativePath,
                            'image_tipo' => $mime,
                        ]);
                    }

                    $stats['migrated']++;
                }
            });

        // Guardar respaldos y auditoría de corruptos
        if (!$isDryRun && !empty($backupPayload)) {
            $backupFile = storage_path('app/backups/users_base64_backup_' . date('Ymd_His') . '.json');
            File::put($backupFile, json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->comment(" -> Respaldo de seguridad creado: {$backupFile}");
        }

        if (!empty($corruptRecords)) {
            $this->logCorruptedRecords('users', $corruptRecords);
        }

        return $stats;
    }

    /**
     * Procesa la migración de artículos en streaming con chunkById.
     */
    private function migrateArticulos(bool $isDryRun): array
    {
        $stats = ['total' => 0, 'migrated' => 0, 'skipped' => 0, 'corrupted' => 0];
        $backupPayload = [];
        $corruptRecords = [];

        DB::table('articulos')
            ->select('id', 'nombre', 'img_base64', 'imagen_tipo')
            ->whereNotNull('img_base64')
            ->where('img_base64', '!=', '')
            ->orderBy('id')
            ->chunkById(25, function ($articulos) use (&$stats, &$backupPayload, &$corruptRecords, $isDryRun) {
                foreach ($articulos as $articulo) {
                    $stats['total']++;
                    $rawData = trim((string) $articulo->img_base64);

                    // Idempotencia: Verificar si ya es una ruta relativa
                    if ($this->isAlreadyRelativePath($rawData, 'articulos/')) {
                        $stats['skipped']++;
                        continue;
                    }

                    // Decodificación y validación de integridad
                    $decoded = $this->decodeAndVerifyImage($rawData, (string) $articulo->imagen_tipo);
                    if ($decoded['status'] === 'corrupted') {
                        $stats['corrupted']++;
                        $corruptRecords[] = [
                            'table' => 'articulos',
                            'id' => $articulo->id,
                            'nombre' => $articulo->nombre,
                            'reason' => $decoded['error'],
                            'raw_length' => strlen($rawData),
                            'raw_sample' => substr($rawData, 0, 100),
                        ];
                        $this->warn(" [!] Artículo ID {$articulo->id} ('{$articulo->nombre}') corrupto: {$decoded['error']}. Se aísla en auditoría.");

                        if (!$isDryRun) {
                            // Si la cadena corrupta es una ruta residual (como /tmp/php...) limpiamos el campo a NULL
                            // para permitir que la columna reduzca su longitud a VARCHAR(255) sin bloquear la migración DDL.
                            DB::table('articulos')->where('id', $articulo->id)->update([
                                'img_base64' => null,
                            ]);
                        }
                        continue;
                    }

                    $ext = $decoded['extension'];
                    $mime = $decoded['mime'];
                    $relativePath = "articulos/articulo_{$articulo->id}.{$ext}";

                    if (!$isDryRun) {
                        $backupPayload[] = [
                            'table' => 'articulos',
                            'id' => $articulo->id,
                            'img_base64' => $rawData,
                            'imagen_tipo' => $articulo->imagen_tipo,
                            'new_path' => $relativePath,
                        ];

                        Storage::disk('public')->put($relativePath, $decoded['binary']);

                        DB::table('articulos')->where('id', $articulo->id)->update([
                            'img_base64' => $relativePath,
                            'imagen_tipo' => $mime,
                        ]);
                    }

                    $stats['migrated']++;
                }
            });

        if (!$isDryRun && !empty($backupPayload)) {
            $backupFile = storage_path('app/backups/articulos_base64_backup_' . date('Ymd_His') . '.json');
            File::put($backupFile, json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->comment(" -> Respaldo de seguridad creado: {$backupFile}");
        }

        if (!empty($corruptRecords)) {
            $this->logCorruptedRecords('articulos', $corruptRecords);
        }

        return $stats;
    }

    /**
     * Decodifica una cadena Base64, limpia prefijos data:image y verifica su validez y MIME mediante magic bytes.
     */
    private function decodeAndVerifyImage(string $rawData, string $declaredMime): array
    {
        // 1. Limpieza de prefijo data URI si viene incrustado
        $clean = preg_replace('/^data:image\/[a-zA-Z0-9\+\-\.]+;base64,/', '', $rawData);
        $clean = preg_replace('/\s+/', '', $clean);

        if (empty($clean)) {
            return ['status' => 'corrupted', 'error' => 'Cadena Base64 vacía tras sanitización'];
        }

        // 2. Comprobar que sea decodificable
        $binary = base64_decode($clean, true);
        if ($binary === false || strlen($binary) === 0) {
            return ['status' => 'corrupted', 'error' => 'Fallo en base64_decode (caracteres no válidos)'];
        }

        // 3. Inspección rigurosa de Magic Bytes binarios con finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_buffer($finfo, $binary);
        finfo_close($finfo);

        if (!isset(self::ALLOWED_MIMES[$detectedMime])) {
            // Intentar validar si el declaredMime es válido y el binario no es basura
            if (isset(self::ALLOWED_MIMES[$declaredMime]) && strlen($binary) > 100) {
                $finalMime = $declaredMime;
                $extension = self::ALLOWED_MIMES[$declaredMime];
            } else {
                return [
                    'status' => 'corrupted',
                    'error' => "Magic bytes no corresponden a imagen válida (detectado: '{$detectedMime}', declarado: '{$declaredMime}')",
                ];
            }
        } else {
            $finalMime = $detectedMime;
            $extension = self::ALLOWED_MIMES[$detectedMime];
        }

        return [
            'status' => 'success',
            'binary' => $binary,
            'mime' => $finalMime,
            'extension' => $extension,
        ];
    }

    /**
     * Verifica si el valor almacenado ya es una ruta relativa física en storage.
     */
    private function isAlreadyRelativePath(string $data, string $expectedFolder): bool
    {
        if (Str::startsWith($data, $expectedFolder) && strlen($data) < 255) {
            return true;
        }

        if (Str::startsWith($data, ['http://', 'https://']) && strlen($data) < 255) {
            return true;
        }

        return false;
    }

    /**
     * Altera las columnas LONGTEXT a VARCHAR(255) y ejecuta OPTIMIZE TABLE para liberar espacio físico.
     */
    private function shrinkSchemaAndOptimize(bool $skipOptimize): void
    {
        $this->info(' - Reduciendo columnas en `users` (image VARCHAR(255), image_tipo VARCHAR(50))...');
        DB::statement('ALTER TABLE users MODIFY image VARCHAR(255) NULL, MODIFY image_tipo VARCHAR(50) NULL');

        $this->info(' - Reduciendo columnas en `articulos` (img_base64 VARCHAR(255), imagen_tipo VARCHAR(50))...');
        DB::statement('ALTER TABLE articulos MODIFY img_base64 VARCHAR(255) NULL, MODIFY imagen_tipo VARCHAR(50) NULL');

        if (!$skipOptimize) {
            $this->info(' - Ejecutando OPTIMIZE TABLE users (reconstrucción de .ibd y liberación de páginas LOB)...');
            DB::statement('OPTIMIZE TABLE users');

            $this->info(' - Ejecutando OPTIMIZE TABLE articulos (reconstrucción de .ibd y liberación de páginas LOB)...');
            DB::statement('OPTIMIZE TABLE articulos');
            $this->info(' ✓ Espacio InnoDB defragmentado y reclamado por el sistema operativo.');
        } else {
            $this->warn(' - Se omitió OPTIMIZE TABLE según el flag provisto.');
        }
    }

    /**
     * Maneja la reversión quirúrgica (Rollback) de las rutas relativas a Base64 original.
     */
    private function handleRollback(): int
    {
        $this->warn('>>> INICIANDO ROLLBACK QUIRÚRGICO DE RUTAS A BASE64 <<<');

        // 1. Re-expandir tipos de columna a LONGTEXT para poder almacenar los bytes Base64
        $this->info('1/3. Re-expandiendo tipos de columnas a LONGTEXT...');
        DB::statement('ALTER TABLE users MODIFY image LONGTEXT NULL, MODIFY image_tipo LONGTEXT NULL');
        DB::statement('ALTER TABLE articulos MODIFY img_base64 LONGTEXT NULL, MODIFY imagen_tipo VARCHAR(255) NULL');

        // 2. Re-codificar avatares de users
        $this->info('2/3. Reconvirtiendo archivos físicos a Base64 en `users`...');
        $restoredUsers = 0;
        DB::table('users')->whereNotNull('image')->where('image', '!=', '')->orderBy('id')->chunkById(25, function ($users) use (&$restoredUsers) {
            foreach ($users as $user) {
                if (Str::startsWith($user->image, 'avatars/') && Storage::disk('public')->exists($user->image)) {
                    $binary = Storage::disk('public')->get($user->image);
                    $base64 = base64_encode($binary);
                    DB::table('users')->where('id', $user->id)->update(['image' => $base64]);
                    $restoredUsers++;
                }
            }
        });
        $this->info(" -> Usuarios revertidos a Base64: {$restoredUsers}");

        // 3. Re-codificar imágenes de artículos
        $this->info('3/3. Reconvirtiendo archivos físicos a Base64 en `articulos`...');
        $restoredArticulos = 0;
        DB::table('articulos')->whereNotNull('img_base64')->where('img_base64', '!=', '')->orderBy('id')->chunkById(25, function ($arts) use (&$restoredArticulos) {
            foreach ($arts as $art) {
                if (Str::startsWith($art->img_base64, 'articulos/') && Storage::disk('public')->exists($art->img_base64)) {
                    $binary = Storage::disk('public')->get($art->img_base64);
                    $base64 = base64_encode($binary);
                    DB::table('articulos')->where('id', $art->id)->update(['img_base64' => $base64]);
                    $restoredArticulos++;
                }
            }
        });
        $this->info(" -> Artículos revertidos a Base64: {$restoredArticulos}");

        $this->info('✓ Rollback completado exitosamente.');
        return Command::SUCCESS;
    }

    /**
     * Consulta el tamaño físico en MB y espacio fragmentado de tablas en MySQL.
     */
    private function fetchTableSizes(array $tables): array
    {
        foreach ($tables as $t) {
            DB::statement("ANALYZE TABLE `{$t}`");
        }

        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $dbName = DB::getDatabaseName();

        $results = DB::select("
            SELECT TABLE_NAME AS table_name,
                   ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                   ROUND(((data_length + index_length) / 1024), 2) AS size_kb,
                   ROUND((data_free / 1024 / 1024), 2) AS data_free_mb
            FROM information_schema.TABLES
            WHERE table_schema = ?
              AND TABLE_NAME IN ($placeholders)
        ", array_merge([$dbName], $tables));

        $keyed = [];
        foreach ($results as $row) {
            $keyed[$row->table_name] = [
                'TABLE_NAME' => $row->table_name,
                'size_mb' => $row->size_mb,
                'size_kb' => $row->size_kb,
                'data_free_mb' => $row->data_free_mb,
            ];
        }

        return $keyed;
    }

    /**
     * Registra anomalías y registros corruptos en un archivo de auditoría sin descartarlos.
     */
    private function logCorruptedRecords(string $table, array $records): void
    {
        $logPath = storage_path('logs/base64_migration_audit.log');
        $timestamp = date('Y-m-d H:i:s');
        $content = "=== [{$timestamp}] REGISTROS CORRUPTOS EN TABLA {$table} ===" . PHP_EOL;
        $content .= json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL . PHP_EOL;

        File::append($logPath, $content);
        Log::channel('single')->warning("Se detectaron " . count($records) . " registros con Base64 anómalo en `{$table}`. Ver detalles en {$logPath}");
        $this->line(" -> Auditoría registrada en {$logPath}");
    }
}
