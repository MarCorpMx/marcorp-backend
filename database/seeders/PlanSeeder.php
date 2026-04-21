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

            // WEB
            'web' => [
                [
                    'sort_order' => 1,
                    'key' => 'free',
                    'name' => 'Web Free',
                    'description' => 'Página básica informativa',
                    'price' => 0,
                ],
                [
                    'sort_order' => 2,
                    'key' => 'basic',
                    'name' => 'Web Basic',
                    'description' => 'Web profesional con secciones personalizadas',
                    'price' => 299,
                ],
                [
                    'sort_order' => 3,
                    'key' => 'pro',
                    'name' => 'Web Pro',
                    'description' => 'Web avanzada con SEO y formularios',
                    'price' => 599,
                ],
                [   // Se creo para que no fallen los seeders ye tener roor con todo premium
                    'sort_order' => 4,
                    'key' => 'premium',
                    'name' => 'Web Premium',
                    'description' => 'Web avanzada con SEO y formularios',
                    'price' => 599,
                ],
            ],

            // CITAS
            'citas' => [
                [
                    'sort_order' => 1,
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
                ],
                [
                    'sort_order' => 2,
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
                ],
                [
                    'sort_order' => 3,
                    'key' => 'pro',
                    'name' => 'Plan Profesional',
                    'description' => 'Para clínicas y equipos pequeños que necesitan funciones avanzadas.',
                    'price' => 799.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => true, // plan estrella
                    'is_limited' => false,
                    'max_sales' => null,
                ],
                [
                    'sort_order' => 4,
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
                ],
                [
                    'sort_order' => 5,
                    'key' => 'founder',
                    'name' => 'Founder (Acceso Anticipado)',
                    'description' => 'Acceso completo al sistema con precio preferencial congelado de por vida. Exclusivo para los primeros usuarios.',
                    'price' => 7999, 
                    'billing_period' => 'yearly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => true,
                    'max_sales' => 20,
                    'metadata' => [
                        'badge' => 'founder',
                        'label' => 'Acceso anticipado',
                        'early_access' => true,
                        'priority_support' => true,
                        'price_locked' => true
                    ]
                ],
            ],

            // INVENTARIOS
            /*'inventarios' => [
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
            ],*/

            // 🎓 ESCOLAR
            /*'escolar' => [
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
                [
                    'key' => 'pro',
                    'name' => 'Escuela Pro',
                    'description' => 'Escuelita completita',
                    'price' => 2000,
                ],
            ],*/
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
                    collect($planData)->except(['key', 'subsystem_id'])->toArray()
                );
            }
        }
    }
}
