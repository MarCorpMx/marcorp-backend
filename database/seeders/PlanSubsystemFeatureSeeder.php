<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Subsystem;
use App\Models\Feature;
use App\Models\PlanSubsystemFeature;

class PlanSubsystemFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $plans = Plan::all()->keyBy('key');
        $subsystems = Subsystem::all()->keyBy('key');

        /*
        |--------------------------------------------------------------------------
        | REGLAS POR PLAN / SISTEMA
        |--------------------------------------------------------------------------
        */
        $rules = [

            // ADDONS
            /*
                +5 servicios → $49/mes
                +1 staff → $99/mes
                +100 emails → $49
                Quitar "Enviado con Rombi"
            */

            /*
            |--------------------------------------------------------------------------
            | SISTEMA: CITAS
            |--------------------------------------------------------------------------
            */

            // FREE – CITAS -> link público

            /*
            ✔ 60 citas / mes
            ✔ clientes ilimitados  
            ✔ servicios: 5 (3 variantes)
            ✔ 1 sucursal
            ✔ 1 usuario
            15 recordatorios email
            */
            [
                'plan' => 'free',
                'subsystem' => 'citas',
                'features' => [
                    'dashboard' => ['enabled' => true],
                    'agenda'    => ['enabled' => true,  'limit' => 5],     // 30 citas al mes
                    'clients'   => ['enabled' => true,  'limit' => 100],     // 30 clientes
                    'services'  => ['enabled' => true,  'limit' => 5],      // 5 servicios - 3 variantes por servicio
                    'booking'   => ['enabled' => true],
                    'schedule'  => ['enabled' => true, 'visible' => true], // no BLOQUEADO
                    'reminders' => ['enabled' => false, 'visible' => true, 'limit' => 3], // BLOQUEADO  / 10–20 recordatorios / mes
                    'reports'   => ['enabled' => false, 'visible' => true], // Sin reportes
                    'team'      => ['enabled' => false, 'visible' => true, 'limit' => 1], // Sin equipo

                    'settings' => ['enabled' => true],
                    'profile'           => ['enabled' => true],
                    'branches'          => ['enabled' => true, 'limit' => 1],        // 1 sucursal
                    'schedule_config'   => ['enabled' => true],
                    'payments'          => ['enabled' => false, 'visible' => true],
                    'advanced'          => ['enabled' => false, 'visible' => true, 'limit' => 0],
                ],
            ],

            // BASIC – CITAS
            /*
                ✔ 150 citas
                ✔ servicios: 10
                ✔ 100 recordatorios
                ✔ 2 staff
             */
            [
                'plan' => 'basic',
                'subsystem' => 'citas',
                'features' => [
                    'dashboard' => ['enabled' => true],
                    'agenda'   => ['enabled' => true, 'limit' => 150],
                    'clients'  => ['enabled' => true, 'limit' => 200],
                    'services' => ['enabled' => true, 'limit' => 10], // 10 servicios - 10 variantes por servicio
                    'booking'   => ['enabled' => true],
                    'schedule'  => ['enabled' => true],
                    'reminders' => ['enabled' => true, 'limit' => 100],     // 100 envios por mes
                    'reports'   => ['enabled' => false, 'visible' => true], // BLOQUEADO
                    'team'      => ['enabled' => true, 'limit' => 2],       // 2 miembros de equipo

                    'settings' => ['enabled' => true],
                    'profile'           => ['enabled' => true],
                    'branches'          => ['enabled' => true, 'limit' => 1],        // 1 sucursal
                    'schedule_config'   => ['enabled' => true],
                    'payments'          => ['enabled' => false, 'visible' => true],
                    'advanced'          => ['enabled' => true, 'limit' => 1],

                    /* ADVANCED
                    notificaciones básicas
                    email básico
                    ❌ NO dominio
                    ❌ NO SMTP
                    ❌ NO branding*/
                ],
            ],

            // PRO – CITAS
            /*
                ✔ citas ilimitadas
                ✔ recordatorios 1000
                ✔ reportes
                ✔ 5 staff
                ✔ 3 sucursales
            */
            [
                'plan' => 'pro',
                'subsystem' => 'citas',
                'features' => [
                    'dashboard' => ['enabled' => true],
                    'agenda'    => ['enabled' => true],                 // Ilimitadas
                    'clients'   => ['enabled' => true],                 // Ilimitados
                    'services'  => ['enabled' => true],                 // Ilimitados
                    'booking'   => ['enabled' => true],
                    'schedule'  => ['enabled' => true],
                    'reminders' => ['enabled' => true, 'limit' => 1000],
                    'reports'   => ['enabled' => true],                 // Con reportes
                    'team'      => ['enabled' => true, 'limit' => 5],   // 5 miembros

                    'settings' => ['enabled' => true],
                    'profile'           => ['enabled' => true],
                    'branches'          => ['enabled' => true, 'limit' => 3],    // 3 sucursales
                    'schedule_config'   => ['enabled' => true],
                    'payments'          => ['enabled' => true],
                    'advanced'          => ['enabled' => true, 'limit' => 2],

                    /* ADVANCED
                    recordatorios
                    branding básico
                    configuraciones de agenda más finas

                    ❌ dominio custom (opcional upsell)
                    ❌ correo corporativo */
                ],
            ],

            // PREMIUM - CITAS
            /*
                ✔ TODO + escalable
                ej:
                - 5 sucursales incluidas
                - +$ por sucursal extra
            */

            [
                'plan' => 'premium',
                'subsystem' => 'citas',
                'features' => [
                    'dashboard' => ['enabled' => true],
                    'agenda'    => ['enabled' => true],
                    'clients'   => ['enabled' => true],
                    'services'  => ['enabled' => true],
                    'booking'   => ['enabled' => true],
                    'schedule'  => ['enabled' => true],
                    'reminders' => ['enabled' => true],
                    'reports'   => ['enabled' => true],
                    'team'      => ['enabled' => true],

                    'settings' => ['enabled' => true],
                    'profile'           => ['enabled' => true],
                    'branches'          => ['enabled' => true],
                    'schedule_config'   => ['enabled' => true],
                    'payments'          => ['enabled' => true],
                    'advanced'          => ['enabled' => true, 'limit' => 3],
                    /* ADVANCED
                    dominio personalizado ✅
                    configuración avanzada de correos ✅
                    plantillas ✅
                    integraciones futuras ✅ */
                ],
            ],

            // FOUNDER - CITAS
            [
                'plan' => 'founder',
                'subsystem' => 'citas',
                'features' => [
                    'dashboard' => ['enabled' => true],
                    'agenda'    => ['enabled' => true],
                    'clients'   => ['enabled' => true],
                    'services'  => ['enabled' => true],
                    'booking'   => ['enabled' => true],
                    'schedule'  => ['enabled' => true],
                    'reminders' => ['enabled' => true],
                    'reports'   => ['enabled' => true],
                    'team'      => ['enabled' => true],

                    'settings' => ['enabled' => true],
                    'profile'           => ['enabled' => true],
                    'branches'          => ['enabled' => true],
                    'schedule_config'   => ['enabled' => true],
                    'payments'          => ['enabled' => true],
                    'advanced'          => ['enabled' => true, 'limit' => 3],
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | SISTEMA: WEB
            |--------------------------------------------------------------------------
            */

            // FREE – WEB
            [
                'plan' => 'free',
                'subsystem' => 'web',
                'features' => [
                    'pages' => ['enabled' => true, 'limit' => 3],
                    'blog'  => ['enabled' => false],
                    'forms' => ['enabled' => true, 'limit' => 1],
                    'seo'   => ['enabled' => false],
                ],
            ],

            // BASIC – WEB
            [
                'plan' => 'basic',
                'subsystem' => 'web',
                'features' => [
                    'pages' => ['enabled' => true, 'limit' => 10],
                    'blog'  => ['enabled' => true, 'limit' => 10],
                    'forms' => ['enabled' => true, 'limit' => 5],
                    'seo'   => ['enabled' => false],
                ],
            ],

            // PRO – WEB
            [
                'plan' => 'pro',
                'subsystem' => 'web',
                'features' => [
                    'pages' => ['enabled' => true],
                    'blog'  => ['enabled' => true],
                    'forms' => ['enabled' => true],
                    'seo'   => ['enabled' => true],
                ],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | PERSISTENCIA
        |--------------------------------------------------------------------------
        */
        foreach ($rules as $rule) {

            $plan = $plans[$rule['plan']] ?? null;
            $subsystem = $subsystems[$rule['subsystem']] ?? null;

            if (!$plan || !$subsystem) {
                continue;
            }

            foreach ($rule['features'] as $featureKey => $config) {

                $feature = Feature::where('subsystem_id', $subsystem->id)
                    ->where('key', $featureKey)
                    ->first();

                if (!$feature) {
                    continue;
                }

                PlanSubsystemFeature::updateOrCreate(
                    [
                        'plan_id'      => $plan->id,
                        'subsystem_id' => $subsystem->id,
                        'feature_id'   => $feature->id,
                    ],
                    [
                        'is_enabled'  => $config['enabled'],
                        'is_visible'  => $config['visible'] ?? true,
                        'limit_value' => $config['limit'] ?? null,
                    ]
                );
            }
        }
    }
}
