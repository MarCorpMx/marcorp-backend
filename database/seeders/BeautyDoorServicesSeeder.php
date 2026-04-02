<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Organization;
use App\Models\Service;
use App\Models\ServiceVariant;
use App\Models\StaffMember;

class BeautyDoorServicesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $organization = Organization::where('slug', 'beauty-door')->first();

            if (!$organization) return;

            $staff = StaffMember::where('organization_id', $organization->id)->get();

            /*
            |--------------------------------------------------------------------------
            | Servicio: Uñas Acrílicas
            |--------------------------------------------------------------------------
            */

            $service = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Uñas Acrílicas',
                ],
                [
                    'active' => true,
                    'color' => '#FFB6C1',
                ]
            );

            $variant = ServiceVariant::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'name' => 'Set completo',
                ],
                [
                    'duration_minutes' => 90,
                    'price' => 500,
                    'max_capacity' => 1,
                    'active' => true,
                ]
            );

            // 🔥 ASIGNAR TODOS LOS STAFF
            $variant->staff()->sync($staff->pluck('id'));

            /*
            |--------------------------------------------------------------------------
            | Servicio: Gelish
            |--------------------------------------------------------------------------
            */

            $service2 = Service::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Gelish',
                ],
                [
                    'active' => true,
                ]
            );

            $variant2 = ServiceVariant::updateOrCreate(
                [
                    'service_id' => $service2->id,
                    'name' => 'Aplicación',
                ],
                [
                    'duration_minutes' => 60,
                    'price' => 250,
                    'max_capacity' => 1,
                    'active' => true,
                ]
            );

            $variant2->staff()->sync($staff->pluck('id'));
        });
    }
}
