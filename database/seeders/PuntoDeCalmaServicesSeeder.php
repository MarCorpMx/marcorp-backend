<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\Branch;
use App\Models\StaffMember;
use App\Models\BranchService;
use App\Models\BranchServiceVariant;
use App\Models\BranchServiceVariantStaff;

class PuntoDeCalmaServicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $organization = Organization::where(
                'slug',
                'punto-de-calma'
            )->firstOrFail();

            $staff = StaffMember::where(
                'organization_id',
                $organization->id
            )->first();

            if (!$staff) {
                $this->command->warn(
                    'No hay staff para asignar servicios.'
                );
                return;
            }

            $branches = Branch::where(
                'organization_id',
                $organization->id
            )->get();

            if ($branches->isEmpty()) {
                $this->command->warn(
                    'No hay sucursales registradas.'
                );
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Catálogo base
            |--------------------------------------------------------------------------
            */

            $catalog = [

                [
                    'service' => [
                        'name' => 'Psicoterapia Humanista',
                        'description' =>
                        'Acompañamiento terapéutico centrado en la persona para fortalecer bienestar emocional y crecimiento personal.',
                        'color' => '#6E8B7B',
                    ],
                    'variants' => [
                        [
                            'name' => 'Sesión individual',
                            'description' =>
                            'Espacio terapéutico individual para trabajar emociones, claridad mental y procesos personales.',
                            'duration_minutes' => 50,
                            'price' => 600,
                            'max_capacity' => 1,
                            'mode' => 'hybrid',
                            'includes_material' => false,
                        ],
                    ],
                ],

                [
                    'service' => [
                        'name' => 'Masaje sanador del alma',
                        'description' =>
                        'Terapia corporal holística orientada a liberar tensión y recuperar equilibrio físico-emocional.',
                        'color' => '#6E8B7B',
                    ],
                    'variants' => [
                        [
                            'name' => 'Sesión individual',
                            'description' =>
                            'Masaje relajante personalizado para descanso profundo y renovación energética.',
                            'duration_minutes' => 60,
                            'price' => 600,
                            'max_capacity' => 1,
                            'mode' => 'presential',
                            'includes_material' => true,
                        ],
                    ],
                ],

                [
                    'service' => [
                        'name' => 'Auriculoterapia',
                        'description' =>
                        'Técnica inspirada en medicina tradicional para apoyar equilibrio integral mediante puntos auriculares.',
                        'color' => '#6E8B7B',
                    ],
                    'variants' => [
                        [
                            'name' => 'Sesión individual',
                            'description' =>
                            'Sesión enfocada en estrés, ansiedad, descanso y armonización general.',
                            'duration_minutes' => 40,
                            'price' => 400,
                            'max_capacity' => 1,
                            'mode' => 'presential',
                            'includes_material' => false,
                        ],
                    ],
                ],

                [
                    'service' => [
                        'name' => 'Arteterapia',
                        'description' =>
                        'Proceso terapéutico creativo para expresar emociones y fomentar autoconocimiento.',
                        'color' => '#8B907E',
                    ],
                    'variants' => [
                        [
                            'name' => 'Sesión individual',
                            'description' =>
                            'Acompañamiento creativo personalizado en un entorno seguro y guiado.',
                            'duration_minutes' => 60,
                            'price' => 600,
                            'max_capacity' => 1,
                            'mode' => 'presential',
                            'includes_material' => true,
                        ],
                        [
                            'name' => 'Sesión grupal',
                            'description' =>
                            'Experiencia grupal enfocada en conexión, expresión colectiva y bienestar compartido.',
                            'duration_minutes' => 60,
                            'price' => 2000,
                            'max_capacity' => 10,
                            'mode' => 'presential',
                            'includes_material' => true,
                        ],
                    ],
                ],

            ];

            /*
            |--------------------------------------------------------------------------
            | Crear por sucursal
            |--------------------------------------------------------------------------
            */

            foreach ($branches as $branchIndex => $branch) {

                foreach ($catalog as $serviceIndex => $item) {

                    $service = BranchService::updateOrCreate(
                        [
                            'organization_id' => $organization->id,
                            'branch_id' => $branch->id,
                            'name' => $item['service']['name'],
                        ],
                        [
                            'description' =>
                            $item['service']['description'],
                            'color' =>
                            $item['service']['color'],
                            'active' => true,
                            'sort_order' => $serviceIndex,
                        ]
                    );

                    foreach (
                        $item['variants']
                        as $variantIndex => $variantData
                    ) {

                        $variant =
                            BranchServiceVariant::updateOrCreate(
                                [
                                    'organization_id' =>
                                    $organization->id,
                                    'branch_id' =>
                                    $branch->id,
                                    'branch_service_id' =>
                                    $service->id,
                                    'name' =>
                                    $variantData['name'],
                                ],
                                [
                                    'description' =>
                                    $variantData['description'],
                                    'duration_minutes' =>
                                    $variantData['duration_minutes'],
                                    'price' =>
                                    $variantData['price'],
                                    'max_capacity' =>
                                    $variantData['max_capacity'],
                                    'mode' =>
                                    $variantData['mode'],
                                    'includes_material' =>
                                    $variantData['includes_material'],
                                    'active' => true,
                                    'sort_order' =>
                                    $variantIndex,
                                ]
                            );

                        BranchServiceVariantStaff::updateOrCreate(
                            [
                                'organization_id' =>
                                $organization->id,
                                'branch_id' =>
                                $branch->id,
                                'branch_service_variant_id' =>
                                $variant->id,
                                'staff_member_id' =>
                                $staff->id,
                            ],
                            [
                                'active' => true,
                                'sort_order' => 0,
                            ]
                        );
                    }
                }
            }

            $this->command->info(
                'Catálogo Punto de Calma creado correctamente.'
            );
        });
    }
}
