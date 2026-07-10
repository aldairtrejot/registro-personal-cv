<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Cv\UsuarioSistema;

class AdminSoporteRhSeeder extends Seeder
{
    public function run(): void
    {
        $user = UsuarioSistema::updateOrCreate(
            ['email' => 'soporte_rh@imssbienestar.gob.mx'],
            [
                'username' => 'soporte_rh',
                'nombre_completo' => 'Soporte RH',
                'password_hash' => Hash::make('rh2025@'),
                'activo' => true,
            ]
        );

        DB::table('profesionalizacion.rel_usuario_rol')->updateOrInsert(
            [
                'id_usuario' => $user->id_usuario,
                'id_rol' => 1,
            ],
            [
                'activo' => true,
            ]
        );
    }
}
