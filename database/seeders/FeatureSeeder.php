<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $subsystemId = 1; // Citas

        $features = [
            [
                'key' => 'dashboard',
                'name' => 'Dashboard',
                'description' => 'Vista general del sistema',
                'menu_label' => 'Dashboard',
                'menu_route' => '/sistemas/citas/dashboard',
                'menu_icon' => 'LayoutDashboard',
                'is_billable' => false,
                'is_core' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'agenda',
                'name' => 'Agenda',
                'description' => 'Gestión de citas y calendario',
                'menu_label' => 'Agenda',
                'menu_route' => '/sistemas/citas/agenda',
                'menu_icon' => 'Calendar',
                'sort_order' => 2,
            ],
            [
                'key' => 'clients',
                'name' => 'Clientes',
                'description' => 'Administración de clientes',
                'menu_label' => 'Clientes',
                'menu_route' => '/sistemas/citas/clientes',
                'menu_icon' => 'UserRound',
                'sort_order' => 3,
            ],
            [
                'key' => 'services',
                'name' => 'Servicios',
                'description' => 'Servicios ofrecidos',
                'menu_label' => 'Servicios',
                'menu_route' => '/sistemas/citas/servicios',
                'menu_icon' => 'Briefcase',
                'sort_order' => 4,
            ],
            [
                'key' => 'schedule',
                'name' => 'Horarios',
                'description' => 'Configuración de horarios',
                'menu_label' => 'Horarios',
                'menu_route' => '/sistemas/citas/horarios',
                'menu_icon' => 'Clock',
                'sort_order' => 5,
            ],
            [
                'key' => 'reminders',
                'name' => 'Recordatorios',
                'description' => 'Recordatorios automáticos',
                'menu_label' => 'Recordatorios',
                'menu_route' => '/sistemas/citas/recordatorios',
                'menu_icon' => 'Bell',
                'sort_order' => 6,
            ],
            [
                'key' => 'reports',
                'name' => 'Reportes',
                'description' => 'Reportes y métricas',
                'menu_label' => 'Reportes',
                'menu_route' => '/sistemas/citas/reportes',
                'menu_icon' => 'BarChart',
                'sort_order' => 7,
            ],
            [
                'key' => 'team',
                'name' => 'Equipo',
                'description' => 'Gestión de colaboradores',
                'menu_label' => 'Equipo',
                'menu_route' => '/sistemas/citas/equipo',
                'menu_icon' => 'UsersRound',
                'sort_order' => 8,
            ],
            [
                'key' => 'settings',
                'name' => 'Configuración',
                'description' => 'Configuración del sistema',
                'menu_label' => 'Configuración',
                'menu_route' => '/sistemas/citas/configuracion',
                'menu_icon' => 'Settings',
                'is_billable' => false,
                'is_core' => true,
                'sort_order' => 9,
            ],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(
                [
                    'subsystem_id' => $subsystemId,
                    'key' => $feature['key'],
                ],
                array_merge($feature, [
                    'subsystem_id' => $subsystemId,
                ])
            );
        }
    }
}
