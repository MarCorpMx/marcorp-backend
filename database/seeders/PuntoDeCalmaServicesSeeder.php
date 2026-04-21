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
            | Helper
            |--------------------------------------------------------------------------
            */
            $attachToAllBranches = function ($variant) use ($staff, $branches) {
                foreach ($branches as $branch) {
                    DB::table('service_variant_staff')->updateOrInsert([
                        'service_variant_id' => $variant->id,
                        'staff_member_id' => $staff->id,
                        'branch_id' => $branch->id,
                    ], [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);
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
                    'description' => 'Acompañamiento terapéutico centrado en la persona...',
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
                    'description' => 'Terapia corporal holística...',
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
                    'description' => 'Medicina tradicional china...',
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
                    'description' => 'Expresión artística terapéutica...',
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
