<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\OrganizationSubsystem;
use App\Models\OrganizationNotificationSetting;
use App\Models\Subsystem;
use App\Models\Plan;

class RootUserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1️⃣ Usuario ROOT
        |--------------------------------------------------------------------------
        */
        $user = User::firstOrCreate(
            ['email' => 'soporte@marcorp.com'],
            [
                'username' => 'omar_root',
                'name' => 'Omar Antunez',
                'first_name' => 'Omar',
                'last_name' => 'Antunez',
                'password' => Hash::make('Root@123456'),
                //'password' => Hash::make(env('ROOT_PASSWORD', Str::random(16))),
                'status' => 'active',
                'email_verified' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2️⃣ Organización ROOT
        |--------------------------------------------------------------------------
        */
        $organization = Organization::firstOrCreate(
            ['slug' => 'marcorp'],
            [
                'name'           => 'MarCorp',
                'type'           => 'root',
                'is_internal'    => true,

                'owner_user_id'  => $user->id,
                'status'         => 'active',

                'email'          => 'soporte@marcorp.mx',
                'phone'          => [
                    'principal' => '777 482 1997',
                    'personal'  => '770 202 1345',
                ],

                // Branding MarCorp
                'theme_key'       => 'marcorp',
                'primary_color'   => '#18C48F',
                'secondary_color' => '#38BDF8',
                'logo_url'        => '/branding/marcorp-logo.svg',
                'white_label'     => false,

                // Dominio
                'primary_domain' => 'marcorp.mx',
                'domains'        => ['www.marcorp.mx'],
                'force_https'    => true,

                'metadata' => [
                    'timezone' => 'America/Mexico_City',
                    'notes'    => 'Organización root del sistema',
                    'contact_success_message' => 'Tu mensaje fue recibido',
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3️⃣ Usuario como OWNER / ROOT
        |--------------------------------------------------------------------------
        */
        OrganizationUser::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'root',
                'status' => 'active',
                'joined_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Configuraciones de Mail
        |--------------------------------------------------------------------------
        */

        OrganizationNotificationSetting::updateOrCreate(
            ['organization_id' => $organization->id],
            [
                'notification_to' => [
                    'soporte@marcorp.mx',
                ],
                'notification_bcc' => [
                    'omar.marcorp@gmail.com',
                ],
                'auto_reply_enabled' => false,
                'emergency_footer_enabled' => false,
                'office_hours' => [
                    'start' => '08:00',
                    'end' => '17:00',
                    'timezone' => 'America/Mexico_City',
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4️⃣ Obtener PLAN PRO
        |--------------------------------------------------------------------------
        */
        //$proPlan = Plan::where('key', 'pro')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Asignar TODOS los subsistemas con PLAN PRO
        |--------------------------------------------------------------------------
        */
        $subsystems = Subsystem::all();

        foreach ($subsystems as $subsystem) {
            /*
        |--------------------------------------------------------------------------
        | Obtener PLAN PRO
        |--------------------------------------------------------------------------
        */
            $proPlan = Plan::where('subsystem_id', $subsystem->id)
                ->where('key', 'pro')
                ->first();

            if (!$proPlan) {
                continue; // o lanzar excepción
            }
            /********************** */

            OrganizationSubsystem::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'subsystem_id' => $subsystem->id,
                ],
                [
                    'plan_id' => $proPlan->id,
                    'status' => 'active',
                    'started_at' => now(),
                    'is_paid' => true,
                ]
            );
        }
    }
}
