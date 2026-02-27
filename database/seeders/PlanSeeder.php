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
                    'description' => 'Para comenzar a usar el sistema con funcionalidades básicas.',
                    'price' => 0.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => false,
                    'max_sales' => null,
                    'sales_count' => 0,
                ],
                [
                    'key' => 'basic',
                    'name' => 'Plan Básico',
                    'description' => 'Ideal para profesionales independientes que inician.',
                    'price' => 299.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => false,
                    'max_sales' => null,
                    'sales_count' => 0,
                ],
                [
                    'key' => 'pro',
                    'name' => 'Plan Profesional',
                    'description' => 'Para clínicas y equipos pequeños que necesitan funciones avanzadas.',
                    'price' => 799.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => true, // ⭐ tu plan estrella
                    'is_limited' => false,
                    'max_sales' => null,
                    'sales_count' => 0,
                ],
                [
                    'key' => 'premium',
                    'name' => 'Plan Premium',
                    'description' => 'Para clínicas establecidas con mayor volumen y necesidades avanzadas.',
                    'price' => 1999.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => false,
                    'max_sales' => null,
                    'sales_count' => 0,
                ],
                [
                    'key' => 'founder_lifetime',
                    'name' => 'Founder Lifetime (Edición Limitada)',
                    'description' => 'Acceso de por vida al Plan Profesional para miembros fundadores. Cupos limitados.',
                    'price' => 8999.00,
                    'billing_period' => 'lifetime',
                    'is_active' => true,
                    'is_visible' => false, // 🔒 no aparece en pricing público
                    'is_featured' => false,
                    'is_limited' => true,
                    'max_sales' => 20, // 🔥 límite real
                    'sales_count' => 0,
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

                $defaults = [
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => false,
                    'max_sales' => null,
                    'sales_count' => 0,
                ];

                $planData = array_merge($defaults, $planData);
                $planData['subsystem_id'] = $subsystem->id;

                Plan::updateOrCreate(
                    [
                        'subsystem_id' => $subsystem->id,
                        'key' => $planData['key'],
                    ],
                    $planData
                );
            }
        }
    }
}
