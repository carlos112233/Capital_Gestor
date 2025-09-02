<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
      public function run()
    {
        $faker = Faker::create();

        $usuarios = [
            'Temo',
            'Fercho',
            'Ronaldo',
            'Justin Dorantes',
            'Jhanorit',
            'Juancho',
            'Erika LC',
            'Pepe',
            'Jhon',
            'Marlen',
            'Eve',
            'Paola Ramirez',
            'Diego Juárez',
            'Sra Mago',
            'Areli',
            'Angeles',
            'Sandra',
            'Pisos',
            'Lucí',
            'Luis Lozada',
            'Mario',
            'Angelllo',
            'Charly',
            'Jacob',
            'Verito'
        ];

        foreach ($usuarios as $index => $nombre) {
            $user = User::create([
                'name' => $nombre,
                'email' => $faker->unique()->safeEmail, // correo aleatorio
                'password' => Hash::make(($index % 8) + 1), // contraseñas del 1 al 8
            ]);

            // Asignar rol 2
            $user->roles()->attach(2);
        }
    }
}
