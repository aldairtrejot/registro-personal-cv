<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Cv\UsuarioSistema; // ⚠️ usa tu modelo real

class AdminSoporteRhSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear/actualizar usuario
        $user = UsuarioSistema::updateOrCreate(
            ['email' => 'soporte_rh@imssbienestar.gob.mx'],
            [
                'nombre'   => 'Soporte RH',      // cambia al campo de nombre que tenga tu tabla
                'password' => Hash::make('rh2025@'),
                'activo'   => true,             // si tu tabla tiene este campo
            ]
        );

        // 2. Asignar rol ADMIN (id_rol = 1)
        DB::table('profesionalizacion.rel_usuario_rol')->updateOrInsert(
            [
                'id_usuario' => $user->id_usuario, // PK de usuarios_sistema
                'id_rol'     => 1,                // ADMIN
            ],
            [
                'activo'     => true,
            ]
        );
    }
}
