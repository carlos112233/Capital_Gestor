<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call(RoleSeeder::class);
        $this->call(UsuariosSeeder::class);

        \App\Models\Articulo::firstOrCreate(
            ['nombre' => 'Pago saldado'],
            ['precio' => 0, 'stock' => 999999, 'descripcion' => 'Artículo del sistema para saldar adeudos']
        );
        \App\Models\Articulo::firstOrCreate(
            ['nombre' => 'Abono'],
            ['precio' => 0, 'stock' => 999999, 'descripcion' => 'Artículo del sistema para abonos']
        );
    }
}
