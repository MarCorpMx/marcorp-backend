<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['key' => 'root', 'name' => 'Root'],
            ['key' => 'admin', 'name' => 'Administrador'],
            ['key' => 'manager', 'name' => 'Encargado'],
            ['key' => 'user', 'name' => 'Usuario'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['key' => $role['key']],
                $role
            );
        }
    }
}
