<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;

class ClientUserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Usuario Michell
        |--------------------------------------------------------------------------
        */
        $michel = User::firstOrCreate(
            ['email' => 'contacto@punto-de-calma.com'],
            [
                'username'       => 'michell_admin',
                'name'           => 'Michell Martínez',
                'first_name'     => 'Michell',
                'last_name'      => 'Martínez Hernández',
                'password'       => Hash::make('Admin@123456'),
                'phone'          => [
                    'personal'  => '777 351 9640',
                ],
                'status'         => 'active',
                'email_verified' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Organización Punto de Calma
        |--------------------------------------------------------------------------
        */
        $organization = Organization::updateOrCreate(
            ['slug' => 'punto-de-calma'],
            [
                'name'           => 'Punto de Calma',
                'type'           => 'client',
                'is_internal'    => false,

                'owner_user_id'  => $michel->id,
                'status'         => 'active',

                'email'          => 'contacto@punto-de-calma.com',
                'phone'          => [
                    'consultorio' => 'xx xxxx xxxx',
                    'personal'    => '777 351 9640',
                ],

                // Branding
                'theme_key'      => 'punto-de-calma',
                'primary_color'  => '#8B907E',
                'secondary_color'=> '#EEE6DC',
                'logo_url'       => '/branding/punto-de-calma-logo.svg',
                'white_label'    => false,

                // Dominio
                'primary_domain' => 'punto-de-calma.com',
                'domains'        => [
                    'www.punto-de-calma.com',
                    'qa.punto-de-calma.com',
                ],
                'force_https'    => true,

                'metadata' => [
                    'timezone' => 'America/Mexico_City',
                    'initial_use_case' => 'web',
                    'contact_success_message' => 'Tu mensaje fue recibido con calma 💚',
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Asociar Michell como OWNER
        |--------------------------------------------------------------------------
        */
        OrganizationUser::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id'         => $michel->id,
            ],
            [
                'role'      => 'owner',
                'status'    => 'active',
                'joined_at' => now(),
            ]
        );
    }
}
