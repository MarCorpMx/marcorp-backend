<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Subsystem;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        // Obtenemos los subsystems por key
        $subsystems = Subsystem::whereIn('key', [
            'web',
            'citas',
            'inventarios',
            'escolar',
        ])->get()->keyBy('key');

        $plans = [

            // 🌐 WEB
            'web' => [
                [
                    'key' => 'free',
                    'name' => 'Web Free',
                    'description' => 'Página básica informativa',
                    'price' => 0,
                ],
                [
                    'key' => 'basic',
                    'name' => 'Web Basic',
                    'description' => 'Web profesional con secciones personalizadas',
                    'price' => 299,
                ],
                [
                    'key' => 'pro',
                    'name' => 'Web Pro',
                    'description' => 'Web avanzada con SEO y formularios',
                    'price' => 599,
                ],
            ],

            // 📅 CITAS
            'citas' => [
                [
                    'key' => 'free',
                    'name' => 'Plan Gratuito',
                    'description' => 'Para comenzar a usar el sistema',
                    'price' => 0.00,
                ],
                [
                    'key' => 'basic',
                    'name' => 'Plan Básico',
                    'description' => 'Acceso básico con módulos limitados',
                    'price' => 299.00,
                ],
                [
                    'key' => 'pro',
                    'name' => 'Plan Profesional',
                    'description' => 'Acceso completo para pequeños negocios',
                    'price' => 799.00,
                ],
                [
                    'key' => 'premium',
                    'name' => 'Plan Premium',
                    'description' => 'Acceso corporativo con personalización',
                    'price' => 1999.00,
                ],
                [
                    'key' => 'enterprise',
                    'name' => 'Plan Empresarial',
                    'description' => 'Acceso corporativo con personalización',
                    'price' => 1999.00,
                ],
            ],

            // 📦 INVENTARIOS
            'inventarios' => [
                [
                    'key' => 'basic',
                    'name' => 'Inventario Básico',
                    'description' => 'Control simple de productos',
                    'price' => 199,
                ],
                [
                    'key' => 'pro',
                    'name' => 'Inventario Pro',
                    'description' => 'Entradas, salidas y alertas de stock',
                    'price' => 399,
                ],
            ],

            // 🎓 ESCOLAR
            'escolar' => [
                [
                    'key' => 'starter',
                    'name' => 'Escolar Starter',
                    'description' => 'Gestión básica de alumnos',
                    'price' => 0,
                ],
                [
                    'key' => 'school',
                    'name' => 'Escolar Escuela',
                    'description' => 'Grupos, calificaciones y reportes',
                    'price' => 699,
                ],
            ],
        ];

        foreach ($plans as $subsystemKey => $subsystemPlans) {
            $subsystem = $subsystems->get($subsystemKey);

            if (! $subsystem) {
                continue;
            }

            foreach ($subsystemPlans as $planData) {
                Plan::updateOrCreate(
                    [
                        'subsystem_id' => $subsystem->id,
                        'key' => $planData['key'],
                    ],
                    [
                        'name' => $planData['name'],
                        'description' => $planData['description'],
                        'price' => $planData['price'],
                        'billing_period' => 'monthly',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
