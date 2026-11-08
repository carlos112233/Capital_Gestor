<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $vendedorRole = Role::firstOrCreate(['name' => 'vendedor']);
        $cocinaRole = Role::firstOrCreate(['name' => 'cocina']);
        $clienteRole = Role::firstOrCreate(['name' => 'cliente']);

        $usuarios = [
            [
                'name' => 'root',
                'email' => env('ADMIN_DEFAULT_EMAIL', 'admin@tallervelazquez.com'),
                'password' => env('ADMIN_DEFAULT_PASSWORD', 'admin123'),
                'role' => $adminRole->name,
            ],
            [
                'name' => 'Temo',
                'email' => env('VENDEDOR_DEFAULT_EMAIL', 'vendedor@tallervelazquez.com'),
                'password' => env('VENDEDOR_DEFAULT_PASSWORD', 'vendedor123'),
                'role' => $vendedorRole->name,
            ],
            [
                'name' => 'Cocina Local',
                'email' => env('COCINA_DEFAULT_EMAIL', 'cocina@tallervelazquez.com'),
                'password' => env('COCINA_DEFAULT_PASSWORD', 'cocina123'),
                'role' => $cocinaRole->name,
            ],
            [
                'name' => 'Cliente Demo',
                'email' => env('CLIENTE_DEFAULT_EMAIL', 'cliente@tallervelazquez.com'),
                'password' => env('CLIENTE_DEFAULT_PASSWORD', 'cliente123'),
                'role' => $clienteRole->name,
            ],
        ];

        foreach ($usuarios as $usuario) {
            $user = User::updateOrCreate(
                ['email' => $usuario['email']],
                [
                    'name' => $usuario['name'],
                    'password' => Hash::make($usuario['password']),
                ]
            );

            $role = Role::firstOrCreate(['name' => $usuario['role']]);
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
