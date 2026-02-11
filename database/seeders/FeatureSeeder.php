<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | SISTEMA: CITAS (NO TOCAR)
        |--------------------------------------------------------------------------
        */
        $citasSubsystemId = 2; // ← se respeta tal cual

        $citasFeatures = [
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

        foreach ($citasFeatures as $feature) {
            Feature::updateOrCreate(
                [
                    'subsystem_id' => $citasSubsystemId,
                    'key' => $feature['key'],
                ],
                array_merge($feature, [
                    'subsystem_id' => $citasSubsystemId,
                ])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SISTEMA: WEB
        |--------------------------------------------------------------------------
        */
        $webSubsystemId = 1; // ← ajusta si tu ID es otro

        $webFeatures = [
            [
                'key' => 'dashboard',
                'name' => 'Dashboard',
                'description' => 'Resumen del sitio web',
                'menu_label' => 'Dashboard',
                'menu_route' => '/sistemas/web/dashboard',
                'menu_icon' => 'LayoutDashboard',
                'is_billable' => false,
                'is_core' => true,
                'sort_order' => 1,
            ],
            [
                'key' => 'pages',
                'name' => 'Páginas',
                'description' => 'Gestión de páginas del sitio',
                'menu_label' => 'Páginas',
                'menu_route' => '/sistemas/web/paginas',
                'menu_icon' => 'FileText',
                'sort_order' => 2,
            ],
            [
                'key' => 'blog',
                'name' => 'Blog',
                'description' => 'Publicaciones y artículos',
                'menu_label' => 'Blog',
                'menu_route' => '/sistemas/web/blog',
                'menu_icon' => 'BookOpen',
                'sort_order' => 3,
            ],
            [
                'key' => 'forms',
                'name' => 'Formularios',
                'description' => 'Formularios de contacto',
                'menu_label' => 'Formularios',
                'menu_route' => '/sistemas/web/formularios',
                'menu_icon' => 'Mail',
                'sort_order' => 4,
            ],
            [
                'key' => 'seo',
                'name' => 'SEO',
                'description' => 'Optimización para buscadores',
                'menu_label' => 'SEO',
                'menu_route' => '/sistemas/web/seo',
                'menu_icon' => 'Search',
                'sort_order' => 5,
            ],
            [
                'key' => 'settings',
                'name' => 'Configuración',
                'description' => 'Configuración del sitio',
                'menu_label' => 'Configuración',
                'menu_route' => '/sistemas/web/configuracion',
                'menu_icon' => 'Settings',
                'is_billable' => false,
                'is_core' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($webFeatures as $feature) {
            Feature::updateOrCreate(
                [
                    'subsystem_id' => $webSubsystemId,
                    'key' => $feature['key'],
                ],
                array_merge($feature, [
                    'subsystem_id' => $webSubsystemId,
                ])
            );
        }
    }
}
