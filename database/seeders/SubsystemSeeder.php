<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subsystem;

class SubsystemSeeder extends Seeder
{
    public function run(): void
    {
        $subsystems = [
            [
                'key' => 'citas',
                'name' => 'Sistema de Citas',
                'description' => 'Agenda de citas para negocios',
                'is_active' => true,
            ],
            [
                'key' => 'escolar',
                'name' => 'Sistema Escolar',
                'description' => 'Gestión de tareas y actividades académicas',
                'is_active' => true,
            ],
            [
                'key' => 'inventarios',
                'name' => 'Sistema de Inventarios',
                'description' => 'Control de stock y productos',
                'is_active' => true,
            ],
        ];

        foreach ($subsystems as $subsystem) {
            Subsystem::updateOrCreate(
                ['key' => $subsystem['key']],
                $subsystem
            );
        }
    }
}
