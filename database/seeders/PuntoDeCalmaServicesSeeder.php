<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Organization;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Models\StaffMember;
use App\Models\Branch;

class PuntoDeCalmaServicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $organization = Organization::where('slug', 'punto-de-calma')->firstOrFail();

            $staff = StaffMember::where('organization_id', $organization->id)->first();

            if (!$staff) {
                $this->command->warn('No hay staff para asignar servicios');
                return;
            }

            $branches = Branch::where('organization_id', $organization->id)->get();

            /*
            |--------------------------------------------------------------------------
            | Helper: asignar staff + insertar catálogo por sucursal
            |--------------------------------------------------------------------------
            */
            $attachToAllBranches = function ($variant) use ($staff, $branches, $organization) {

                foreach ($branches as $index => $branch) {

                    /*
                    |--------------------------------------------------------------------------
                    | Staff asignado
                    |--------------------------------------------------------------------------
                    */
                    DB::table('service_variant_staff')->updateOrInsert(
                        [
                            'service_variant_id' => $variant->id,
                            'staff_member_id' => $staff->id,
                            'branch_id' => $branch->id,
                        ],
                        [
                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Disponible en sucursal
                    |--------------------------------------------------------------------------
                    */
                    DB::table('branch_service_variant')->updateOrInsert(
                        [
                            'branch_id' => $branch->id,
                            'service_variant_id' => $variant->id,
                        ],
                        [
                            'organization_id' => $organization->id,

                            'name' => $variant->name,
                            'description' => $variant->description,

                            'duration_minutes' => $variant->duration_minutes,
                            'price' => $variant->price,
                            'max_capacity' => $variant->max_capacity,
                            'mode' => $variant->mode,
                            'includes_material' => $variant->includes_material,
                            'active' => $variant->active,

                            'sort_order' => $index,

                            'updated_at' => now(),
                            'created_at' => now(),
                        ]
                    );
                }
            };

            /*
            |--------------------------------------------------------------------------
            | 1 Psicoterapia Humanista
            |--------------------------------------------------------------------------
            */
            $psicoterapia = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Psicoterapia Humanista',
                ],
                [
                    'description' => 'Acompañamiento terapéutico centrado en la persona para fortalecer bienestar emocional y crecimiento personal.',
                    'active' => true,
                    'color' => '#6E8B7B'
                ]
            );

            $variant1 = ServiceVariant::updateOrCreate(
                [
                    'service_id' => $psicoterapia->id,
                    'name' => 'Sesión individual',
                ],
                [
                    'description' => 'Espacio terapéutico individual para trabajar emociones, claridad mental y procesos personales.',
                    'duration_minutes' => 50,
                    'price' => 600,
                    'max_capacity' => 1,
                    'mode' => 'hybrid',
                    'includes_material' => false,
                    'active' => true,
                ]
            );

            $attachToAllBranches($variant1);

            /*
            |--------------------------------------------------------------------------
            | 2 Masaje
            |--------------------------------------------------------------------------
            */
            $masaje = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Masaje sanador del alma',
                ],
                [
                    'description' => 'Terapia corporal holística orientada a liberar tensión y recuperar equilibrio físico-emocional.',
                    'active' => true,
                    'color' => '#6E8B7B'
                ]
            );

            $variant2 = ServiceVariant::updateOrCreate(
                [
                    'service_id' => $masaje->id,
                    'name' => 'Sesión individual',
                ],
                [
                    'description' => 'Masaje relajante personalizado para descanso profundo y renovación energética.',
                    'duration_minutes' => 60,
                    'price' => 600,
                    'max_capacity' => 1,
                    'mode' => 'presential',
                    'includes_material' => true,
                    'active' => true,
                ]
            );

            $attachToAllBranches($variant2);

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
                    'description' => 'Técnica inspirada en medicina tradicional para apoyar equilibrio integral mediante puntos auriculares.',
                    'active' => true,
                    'color' => '#6E8B7B'
                ]
            );

            $variant3 = ServiceVariant::updateOrCreate(
                [
                    'service_id' => $auri->id,
                    'name' => 'Sesión individual',
                ],
                [
                    'description' => 'Sesión enfocada en estrés, ansiedad, descanso y armonización general.',
                    'duration_minutes' => 40,
                    'price' => 400,
                    'max_capacity' => 1,
                    'mode' => 'presential',
                    'includes_material' => false,
                    'active' => true,
                ]
            );

            $attachToAllBranches($variant3);

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
                    'description' => 'Proceso terapéutico creativo para expresar emociones y fomentar autoconocimiento.',
                    'active' => true,
                    'color' => '#8B907E',
                ]
            );

            $variants = [
                [
                    'name' => 'Sesión individual',
                    'description' => 'Acompañamiento creativo personalizado en un entorno seguro y guiado.',
                    'duration_minutes' => 60,
                    'price' => 600,
                    'max_capacity' => 1,
                    'mode' => 'presential',
                    'includes_material' => true,
                    'active' => true,
                ],
                [
                    'name' => 'Sesión grupal',
                    'description' => 'Experiencia grupal enfocada en conexión, expresión colectiva y bienestar compartido.',
                    'duration_minutes' => 60,
                    'price' => 2000,
                    'max_capacity' => 10,
                    'mode' => 'presential',
                    'includes_material' => true,
                    'active' => true,
                ],
            ];

            foreach ($variants as $variantData) {

                $variant = ServiceVariant::updateOrCreate(
                    [
                        'service_id' => $arteterapia->id,
                        'name' => $variantData['name'],
                    ],
                    $variantData
                );

                $attachToAllBranches($variant);
            }
        });
    }
}
