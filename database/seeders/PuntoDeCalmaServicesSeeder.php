<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\Service;
use App\Models\ServiceVariant;

class PuntoDeCalmaServicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $organization = Organization::where('slug', 'punto-de-calma')->first();

            if (!$organization) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | 1 Psicoterapia Humanista (PRIMERO)
            |--------------------------------------------------------------------------
            */

            $psicoterapia = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Psicoterapia Humanista',
                ],
                [
                    'description' => 'Acompañamiento terapéutico centrado en la persona, que promueve el autoconocimiento, la libertad personal y la construcción de una vida con sentido.',
                    'active' => true,
                    'color' => '#6E8B7B'
                ]
            );

            ServiceVariant::updateOrCreate(
                [
                    'service_id' => $psicoterapia->id,
                    'name' => 'Sesión individual',
                ],
                [
                    'duration_minutes' => 50,
                    'price' => 600,
                    'max_capacity' => 1,
                    'mode' => 'hybrid', // presencial o en línea
                    'includes_material' => false,
                    'active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 2 Masaje sanador del alma
            |--------------------------------------------------------------------------
            */

            $masaje = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Masaje sanador del alma',
                ],
                [
                    'description' => 'Terapia corporal de origen holístico que, mediante un contacto consciente y respetuoso, favorece la relajación profunda y el equilibrio emocional.',
                    'active' => true,
                    'color' => '#6E8B7B'
                ]
            );

            ServiceVariant::updateOrCreate(
                [
                    'service_id' => $masaje->id,
                    'name' => 'Sesión individual',
                ],
                [
                    'duration_minutes' => 60,
                    'price' => 600,
                    'max_capacity' => 1,
                    'mode' => 'presential', // presencial o en línea
                    'includes_material' => true,
                    'active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 3 Auriculoterapia
            |--------------------------------------------------------------------------
            */

            $auri = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Auriculoterapia',
                ],
                [
                    'description' => 'Terapia natural basada en la medicina tradicional china que estimula puntos específicos de la oreja para promover el equilibrio físico y emocional.',
                    'active' => true,
                    'color' => '#6E8B7B'
                ]
            );

            ServiceVariant::updateOrCreate(
                [
                    'service_id' => $auri->id,
                    'name' => 'Sesión individual',
                ],
                [
                    'duration_minutes' => 40,
                    'price' => 400,
                    'max_capacity' => 1,
                    'mode' => 'presential', // presencial o en línea
                    'includes_material' => false,
                    'active' => true,
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | 4 Arteterapia
            |--------------------------------------------------------------------------
            */

            $arteterapia = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Arteterapia',
                ],
                [
                    'description' => 'Intervención terapéutica que utiliza la expresión artística como medio para explorar emociones, promover el autoconocimiento y fortalecer la salud emocional.',
                    'active' => true,
                    'color' => '#8B907E',
                ]
            );

            $variants = [
                [
                    'name' => 'Sesión individual',
                    'duration_minutes' => 60,
                    'price' => 600,
                    'max_capacity' => 1,
                    'mode' => 'presential',
                    'includes_material' => true,
                    'active' => true,
                ],
                [
                    'name' => 'Sesión grupal',
                    'duration_minutes' => 60,
                    'price' => 2000,
                    'max_capacity' => 10,
                    'mode' => 'presential',
                    'includes_material' => true,
                    'active' => true,
                ],
            ];

            foreach ($variants as $variantData) {

                ServiceVariant::updateOrCreate(
                    [
                        'service_id' => $arteterapia->id,
                        'name' => $variantData['name'],
                    ],
                    $variantData
                );
            }
        });
    }
}
