<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subsystem;
use App\Models\Role;
use App\Models\UserSubsystem;
use App\Models\UserSubsystemRole;
use Illuminate\Support\Facades\Hash;

class RootUserSeeder extends Seeder
{
    public function run()
    {
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Crear o recuperar usuario ROOT
        |--------------------------------------------------------------------------
        */
        $user = User::firstOrCreate(
            ['email' => 'omar@marcorp.com'],
            [
                'username' => 'omar_root',
                'name' => 'Omar Antunez',
                'first_name' => 'Omar',
                'last_name' => 'Antunez',
                'password' => Hash::make('Root@123456'),
                'status' => 'active',
                'email_verified' => 1,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Crear u obtener rol ROOT
        |--------------------------------------------------------------------------
        */
        $roleRoot = Role::firstOrCreate(
            ['key' => 'root'],
            ['name' => 'Root']
        );

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Obtener todos los subsistemas activos
        |--------------------------------------------------------------------------
        */
        //$subsystems = Subsystem::where('is_active', 1)->get();
        $subsystems = Subsystem::all();

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Asignar ROOT en TODOS los subsistemas
        |--------------------------------------------------------------------------
        */
        foreach ($subsystems as $subsystem) {

            // Relación usuario ↔ subsistema
            $userSubsystem = UserSubsystem::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'subsystem_id' => $subsystem->id,
                ],
                [
                    'is_paid' => 1, // root ignora pagos
                ]
            );

            // Rol ROOT en ese subsistema
            UserSubsystemRole::firstOrCreate(
                [
                    'user_subsystem_id' => $userSubsystem->id,
                    'role_id' => $roleRoot->id,
                ]
            );
        }
    }
}
