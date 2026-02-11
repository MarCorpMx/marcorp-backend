<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Usuario owner: Punto de Calma
        |--------------------------------------------------------------------------
        */
        $pdcOwner = User::where('email', 'contacto@punto-de-calma.com')->first();

        if (!$pdcOwner) {
            $this->command->warn('Usuario owner de Punto de Calma no encontrado.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Organización CLIENTE: Punto de Calma
        |--------------------------------------------------------------------------
        */
        Organization::updateOrCreate(
            ['slug' => 'punto-de-calma'],
            [
                'name'          => 'Punto de Calma',
                'type'          => 'client',
                'is_internal'   => false,

                'owner_user_id' => $pdcOwner->id,
                'status'        => 'active',

                'email' => 'contacto@punto-de-calma.com',
                'phone' => [
                    'consultorio' => 'xx xxxx xxxx',
                    'personal'    => '777 351 9640',
                ],

                // Branding
                'theme_key'       => 'punto-de-calma',
                'primary_color'   => '#8B907E',
                'secondary_color' => '#EEE6DC',
                'logo_url'        => '/branding/punto-de-calma-logo.svg',
                'white_label'     => false,

                // Dominio
                'primary_domain' => 'punto-de-calma.com',
                'domains'        => [
                    'www.punto-de-calma.com',
                    'qa.punto-de-calma.com',
                ],
                'force_https' => true,

                'metadata' => [
                    'timezone'         => 'America/Mexico_City',
                    'initial_use_case' => 'web',
                ],
            ]
        );
    }
}
