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

            /*
            |--------------------------------------------------------------------------
            | SISTEMA: CITAS
            |--------------------------------------------------------------------------
            */

            // FREE – CITAS
            [
                'plan' => 'free',
                'subsystem' => 'citas',
                'features' => [
                    'agenda'    => ['enabled' => true,  'limit' => 50],
                    'clients'   => ['enabled' => true,  'limit' => 50],
                    'services'  => ['enabled' => true,  'limit' => 10],
                    'team'      => ['enabled' => false],
                    'reports'   => ['enabled' => false],
                    'reminders' => ['enabled' => true,  'limit' => 50],
                ],
            ],

            // BASIC – CITAS
            [
                'plan' => 'basic',
                'subsystem' => 'citas',
                'features' => [
                    'agenda'    => ['enabled' => true],
                    'clients'   => ['enabled' => true],
                    'services'  => ['enabled' => true],
                    'team'      => ['enabled' => true, 'limit' => 2],
                    'reports'   => ['enabled' => true],
                    'reminders' => ['enabled' => true],
                ],
            ],

            // PRO – CITAS
            [
                'plan' => 'pro',
                'subsystem' => 'citas',
                'features' => [
                    'agenda'    => ['enabled' => true],
                    'clients'   => ['enabled' => true],
                    'services'  => ['enabled' => true],
                    'team'      => ['enabled' => true, 'limit' => 5],
                    'reports'   => ['enabled' => true],
                    'reminders' => ['enabled' => true],
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
                        'limit_value' => $config['limit'] ?? null,
                    ]
                );
            }
        }
    }
}
