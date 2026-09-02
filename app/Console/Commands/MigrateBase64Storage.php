<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateBase64Storage extends Command
{
    /**
     * El nombre y firma del comando Artisan.
     *
     * @var string
     */
    protected $signature = 'storage:migrate-base64';

    /**
     * La descripción del comando Artisan.
     *
     * @var string
     */
    protected $description = 'Migra imágenes almacenadas en Base64 en la BD MySQL hacia archivos físicos en storage/app/public/';

    /**
     * Ejecuta el comando Artisan.
     */
    public function handle()
    {
        $this->info('Iniciando migración de imágenes en Base64 a Storage físico...');

        $migratedUsers = 0;
        $migratedComprobantes = 0;

        // Ensure target directories exist
        Storage::disk('public')->makeDirectory('avatars');
        Storage::disk('public')->makeDirectory('comprobantes');

        // 1. Migrar usuarios (Avatares / Fotografías)
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $column = property_exists($user, 'imagen') ? 'imagen' : (property_exists($user, 'image_path') ? 'image_path' : null);
            if (!$column) continue;

            $data = $user->{$column};
            if (is_string($data) && Str::startsWith($data, 'data:image')) {
                $filePath = $this->saveBase64Image($data, 'avatars');
                if ($filePath) {
                    DB::table('users')->where('id', $user->id)->update([$column => $filePath]);
                    $migratedUsers++;
                }
            }
        }

        // 2. Migrar comprobantes de pago
        if (DB::getSchemaBuilder()->hasTable('comprobantes')) {
            $comprobantes = DB::table('comprobantes')->get();
            foreach ($comprobantes as $comp) {
                $column = property_exists($comp, 'imagen') ? 'imagen' : (property_exists($comp, 'archivo') ? 'archivo' : null);
                if (!$column) continue;

                $data = $comp->{$column};
                if (is_string($data) && Str::startsWith($data, 'data:image')) {
                    $filePath = $this->saveBase64Image($data, 'comprobantes');
                    if ($filePath) {
                        DB::table('comprobantes')->where('id', $comp->id)->update([$column => $filePath]);
                        $migratedComprobantes++;
                    }
                }
            }
        }

        $this->info("Migración finalizada con éxito.");
        $this->info("- Usuarios migrados: {$migratedUsers}");
        $this->info("- Comprobantes migrados: {$migratedComprobantes}");

        return Command::SUCCESS;
    }

    /**
     * Convierte una cadena Base64 en archivo físico y devuelve la ruta relativa.
     */
    protected function saveBase64Image(string $base64String, string $folder): ?string
    {
        try {
            preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches);
            $extension = $matches[1] ?? 'jpg';
            if ($extension === 'jpeg') $extension = 'jpg';

            $cleanData = preg_replace('/^data:image\/\w+;base64,/', '', $base64String);
            $decodedBinary = base64_decode($cleanData);

            if ($decodedBinary === false) {
                return null;
            }

            $fileName = $folder . '/' . date('Y-m-d') . '_' . Str::random(12) . '.' . $extension;
            Storage::disk('public')->put($fileName, $decodedBinary);

            return $fileName;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
