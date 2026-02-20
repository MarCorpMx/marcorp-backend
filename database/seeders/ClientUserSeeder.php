<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\OrganizationSubsystem;
use App\Models\OrganizationNotificationSetting;
use App\Models\Subsystem;
use App\Models\Plan;

class ClientUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

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
                    //'password'       => Hash::make(env('CLIENT_DEFAULT_PASSWORD', 'Admin@123456')),
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
                    'theme_key'      => 'punto-de-calma',
                    'primary_color'  => '#8B907E',
                    'secondary_color' => '#EEE6DC',
                    'logo_url'       => '/branding/punto-de-calma-logo.svg',
                    'white_label'    => false,
                    'primary_domain' => 'punto-de-calma.com',
                    'domains'        => [
                        'www.punto-de-calma.com',
                        'qa.punto-de-calma.com',
                    ],
                    'force_https'    => true,

                    'metadata' => [
                        'timezone' => 'America/Mexico_City',
                        'initial_use_case' => 'appointments',
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

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ Configuración para mails
            |--------------------------------------------------------------------------
            */
            OrganizationNotificationSetting::updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'notification_to' => [
                        'contacto@punto-de-calma.com',
                    ],
                    'notification_bcc' => [
                        'soporte@marcorp.mx',
                        //'psic.michellemtz@gmail.com'
                    ],
                    'auto_reply_enabled' => true,
                    'emergency_footer_enabled' => true,
                    'office_hours' => [
                        'start' => '09:00',
                        'end' => '18:00',
                        'timezone' => 'America/Mexico_City',
                    ],
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | 4️⃣ Activar Subsistema Citas
            |--------------------------------------------------------------------------
            */

            $appointmentsSubsystem = Subsystem::where('key', 'citas')->firstOrFail();

            $proPlan = Plan::where('subsystem_id', $appointmentsSubsystem->id)
                ->where('key', 'pro')
                ->firstOrFail();

            OrganizationSubsystem::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'subsystem_id'    => $appointmentsSubsystem->id,
                ],
                [
                    'plan_id'    => $proPlan->id,
                    'status'     => 'active',
                    'started_at' => now(),
                    'expires_at'   => now()->addMonth(),
                    'is_paid'    => true,
                ]
            );
        });
    }
}
