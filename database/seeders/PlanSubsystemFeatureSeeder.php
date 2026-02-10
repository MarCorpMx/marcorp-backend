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
        $features = Feature::all()->keyBy('key');

        /**
         * ===========================
         * SISTEMA DE CITAS
         * ===========================
         */

        $rules = [
            // FREE – CITAS
            [
                'plan' => 'free',
                'subsystem' => 'citas',
                'features' => [
                    'agenda' => ['enabled' => true,  'limit' => 50],
                    'clients' => ['enabled' => true,  'limit' => 50],
                    'services' => ['enabled' => true, 'limit' => 10],
                    'team' => ['enabled' => false],
                    'reports' => ['enabled' => false],
                    'reminders' => ['enabled' => true, 'limit' => 50],
                ],
            ],

            // BASIC – CITAS
            [
                'plan' => 'basic',
                'subsystem' => 'citas',
                'features' => [
                    'agenda' => ['enabled' => true],
                    'clients' => ['enabled' => true],
                    'services' => ['enabled' => true],
                    'team' => ['enabled' => true, 'limit' => 2],
                    'reports' => ['enabled' => true],
                    'reminders' => ['enabled' => true],
                ],
            ],

            // PRO – CITAS
            [
                'plan' => 'pro',
                'subsystem' => 'citas',
                'features' => [
                    'agenda' => ['enabled' => true],
                    'clients' => ['enabled' => true],
                    'services' => ['enabled' => true],
                    'team' => ['enabled' => true, 'limit' => 5],
                    'reports' => ['enabled' => true],
                    'reminders' => ['enabled' => true],
                ],
            ],
        ];

        foreach ($rules as $rule) {
            foreach ($rule['features'] as $featureKey => $config) {

                PlanSubsystemFeature::updateOrCreate(
                    [
                        'plan_id' => $plans[$rule['plan']]->id,
                        'subsystem_id' => $subsystems[$rule['subsystem']]->id,
                        'feature_id' => $features[$featureKey]->id,
                    ],
                    [
                        'is_enabled' => $config['enabled'],
                        'limit_value' => $config['limit'] ?? null,
                    ]
                );
            }
        }
    }
}
