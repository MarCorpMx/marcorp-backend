<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subsystem;
use App\Models\Role;
use App\Models\UserSubsystem;
use App\Models\UserSubsystemRole;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class RootUserSeeder extends Seeder
{
    public function run()
    {
        // 1- Crear usuario ROOT si no existe
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

        if ($user->id !== 1) {
            return;
        }

        // 2- Obtener el rol ROOT
        $roleRoot = Role::where('key', 'root')->first();

        if (!$roleRoot) {
            $roleRoot = Role::create([
                'key' => 'root',
                'name' => 'Root',
            ]);
        }

        // 3️⃣ Obtener todos los subsistemas activos
        $subsystems = Subsystem::where('is_active', 1)->get();

        foreach ($subsystems as $subsystem) {

            // 4️⃣ Crear relación usuario ↔ subsistema
            $userSubsystem = UserSubsystem::firstOrCreate([
                'user_id' => $user->id,
                'subsystem_id' => $subsystem->id,
            ], [
                'is_paid' => 1,
            ]);

            // 5️⃣ Asignar rol ROOT a esa relación
            UserSubsystemRole::firstOrCreate([
                'user_subsystem_id' => $userSubsystem->id,
                'role_id' => $roleRoot->id,
            ]);
        }
    }
}
