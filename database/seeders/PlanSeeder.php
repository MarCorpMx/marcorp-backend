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
                    'name' => 'Prueba gratuita',
                    'description' => 'Prueba ROMBI sin compromiso y descubre cómo automatizar tus citas en minutos.',
                    'original_price' => 0.00,
                    'price' => 0.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => false,
                    'max_sales' => null,
                    'support_level' => 'standard',
                    'plan_type' => 'promo',
                    'trial_days' => 30,
                    /*'metadata' => [
                        'badge' => 'trial',
                        'trial_days' => 30
                    ]*/
                ],
                [
                    'sort_order' => 2,
                    'key' => 'basic',
                    'name' => 'Emprendedor',
                    'description' => 'Ideal para profesionales independientes que quieren dejar atrás WhatsApp y comenzar a recibir reservas online de forma profesional.',
                    'original_price' => 299.00,
                    'price' => 299.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => false,
                    'max_sales' => null,
                    'support_level' => 'standard',
                    'plan_type' => 'regular'
                ],
                [
                    'sort_order' => 3,
                    'key' => 'pro',
                    'name' => 'Profesional',
                    'description' => 'Pensado para negocios en crecimiento que necesitan colaboradores, recordatorios automáticos y una operación más eficiente.',
                    'original_price' => 799.00,
                    'price' => 799.00,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => true, // plan estrella
                    'is_limited' => false,
                    'max_sales' => null,
                    'support_level' => 'priority',
                    'plan_type' => 'regular'
                ],
                [
                    'sort_order' => 4,
                    'key' => 'premium',
                    'name' => 'Empresarial',
                    'description' => 'Para clínicas, cadenas y negocios con múltiples sucursales que requieren una solución personalizada.',
                    'price' => 0,
                    'billing_period' => 'monthly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => false,
                    'max_sales' => null,
                    'support_level' => 'vip',
                    'plan_type' => 'regular',
                    'metadata' => [
                        'custom_pricing' => true,
                        'contact_sales' => true
                    ]
                ],
                [
                    'sort_order' => 5,
                    'key' => 'founder',
                    'name' => 'Founder',
                    'description' => 'Obtén acceso completo a ROMBI con precio congelado de por vida. Exclusivo para los primeros negocios que confíen en nosotros.',
                    'original_price' => 9588,
                    'price' => 7999,
                    'billing_period' => 'yearly',
                    'is_active' => true,
                    'is_visible' => true,
                    'is_featured' => false,
                    'is_limited' => true,
                    'max_sales' => 20,
                    'support_level' => 'vip',
                    'plan_type' => 'founder',
                    'starts_at' => now(),
                    'ends_at'   => now()->addMonths(3),
                    'metadata' => [
                        'badge' => 'founder',

                        'label' => 'Precio congelado de por vida',

                        'price_locked' => true,

                        'priority_support' => true,

                        'early_access' => true,

                        'founders_club' => true,

                        'original_price' => 15999,

                        'discount_price' => 7999,

                        'max_slots' => 50
                    ]
                ],

                [
                    'sort_order' => 6,
                    'key' => 'beta',
                    'name' => 'Beta Cerrada',
                    'description' => 'Plan beta para aliados, partners, pruebas internas, clientes VIP',
                    'price' => 7999,
                    'billing_period' => 'lifetime',
                    'is_active' => false,
                    'is_visible' => false,
                    'is_featured' => false,
                    'is_limited' => true,
                    'max_sales' => 50,
                    'metadata' => []
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
