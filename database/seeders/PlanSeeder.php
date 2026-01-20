<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'key' => 'free',
                'name' => 'Plan Gratuito',
                'description' => 'Acceso básico con módulos limitados',
                'price' => 0.00,
            ],
            [
                'key' => 'pro',
                'name' => 'Plan Profesional',
                'description' => 'Acceso completo para pequeños negocios',
                'price' => 299.00,
            ],
            [
                'key' => 'enterprise',
                'name' => 'Plan Empresarial',
                'description' => 'Acceso corporativo con personalización',
                'price' => 999.00,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['key' => $plan['key']],
                $plan
            );
        }
    }
}
