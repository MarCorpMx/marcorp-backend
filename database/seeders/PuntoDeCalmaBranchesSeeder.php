<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Branch;

class PuntoDeCalmaBranchesSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Punto de Calma - Sucursales
        |--------------------------------------------------------------------------
        */

        $organization = Organization::where('slug', 'punto-de-calma')->firstOrFail();

        if (!$organization) {
            $this->command->warn('Organización Punto de Calma no encontrada');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Cuernavaca Jiutepec
        |--------------------------------------------------------------------------
        */
        Branch::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'slug' => $organization->slug . '-cv-jiute',
            ],
            [
                'name' => 'Sucursal Cuernavaca Jiutepec',
                'is_active' => true,
                'is_primary' => false,

                'country' => 'MX',
                'state' => 'Morelos',
                'city' => 'Cuernavaca',
                'zip_code' => '62564',
                'address' => 'Villa Real los Colorines, Jiutepec',

                'timezone' => $organization->timezone,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | CDMX
        |--------------------------------------------------------------------------
        */
        Branch::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'slug' => $organization->slug . '-cdmx',
            ],
            [
                'name' => 'Sucursal CDMX',
                'is_active' => true,
                'is_primary' => false,

                'country' => 'MX',
                'state' => 'CDMX',
                'city' => 'Ciudad de México',
                'zip_code' => '03100',
                'address' => 'Colonia Del Valle, Benito Juárez',

                'timezone' => 'America/Mexico_City',
            ]
        );

        $this->command->info('Sucursales PDC creadas correctamente');
    }
}
