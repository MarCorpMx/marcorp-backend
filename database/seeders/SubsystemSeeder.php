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
                'key' => 'web',
                'name' => 'Web / Forms',
                'description' => 'Formularios, blog, mensajes y contenido web',
                'is_active' => true,
                'is_selectable' => false,
            ],
            [
                'key' => 'citas',
                'name' => 'Sistema de Citas',
                'description' => 'Agenda de citas para negocios',
                'is_active' => true,
                'is_selectable' => true,
            ],
            [
                'key' => 'inventarios',
                'name' => 'Sistema de Inventarios',
                'description' => 'Control de stock y productos',
                'is_active' => false, // opcional
                'is_selectable' => true,
            ],
            [
                'key' => 'escolar',
                'name' => 'Sistema Escolar',
                'description' => 'Gestión académica y escolar',
                'is_active' => false, // opcional
                'is_selectable' => true,
            ]
        ];

        foreach ($subsystems as $subsystem) {
            Subsystem::updateOrCreate(
                ['key' => $subsystem['key']],
                $subsystem
            );
        }
    }
}
