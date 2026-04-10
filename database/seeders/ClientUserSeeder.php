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

use App\Models\UserSubsystemRole;
use App\Models\Role;

class ClientUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Usuario Michelle
            |--------------------------------------------------------------------------
            */
            $michel = User::firstOrCreate(
                ['email' => 'contacto@punto-de-calma.com'],
                [
                    'username'       => 'michelle_admin',
                    'name'           => 'Michelle Martínez',
                    'first_name'     => 'Michelle',
                    'last_name'      => 'Martínez Hernández',
                    //'password'       => Hash::make(env('CLIENT_DEFAULT_PASSWORD', 'Admin@123456')),
                    'password'       => Hash::make('Admin@calma2788'),
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

            //{"number":"7773519640","internationalNumber":"+52 777 351 9640","nationalNumber":"777 351 9640","e164Number":"+527773519640","countryCode":"MX","dialCode":"+52"}
            $organization = Organization::updateOrCreate(
                ['slug' => 'punto-de-calma'],
                [
                    'name'           => 'Punto de Calma',
                    'reference_prefix' => 'PDC',
                    'type'           => 'client',
                    'is_internal'    => false,
                    'owner_user_id'  => $michel->id,
                    'status'         => 'active',
                    'email'          => 'contacto@punto-de-calma.com',
                    'phone'          => [
                        "number" => "7773519640",
                        "internationalNumber" => "+52 777 351 9640",
                        "nationalNumber" => "777 351 9640",
                        "e164Number" => "+527773519640",
                        "countryCode" => "MX",
                        "dialCode" => "+52"
                    ],
                    'website' => 'https://www.punto-de-calma.com',

                    'country' => 'MX',
                    'state' => 'Morelos',
                    'city' => 'Cuernavaca',
                    'zip_code' => '62253',
                    'address' => 'Nueva Polonia 183, zona 1, El Empleado',

                    'theme_key'      => 'punto-de-calma',
                    'primary_color'  => '#8B907E',
                    'secondary_color' => '#EEE6DC',
                    'logo_url'       => 'organizations/logos/punto-de-calma.png',
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
                        'rating' => '4.9',
                        'reviews_count' => '1200',
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Asociar Michelle como OWNER
            |--------------------------------------------------------------------------
            */
            OrganizationUser::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id'         => $michel->id,
                ],
                [
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
                        //'soporte@marcorp.mx',
                        //'psic.michellemtz@gmail.com'
                    ],
                    'auto_reply_enabled' => true,
                    'emergency_footer_enabled' => true,
                    'office_hours' => [
                        'start' => '08:00',
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

            // Asignar role
             $this->assignRole(
                $michel->id,
                $organization->id,
                $appointmentsSubsystem->id,
                'owner'
            );
            
        });
    }

    private function assignRole($userId, $organizationId, $subsystemId, $roleKey)
    {
        $role = Role::where('key', $roleKey)->firstOrFail();

        UserSubsystemRole::updateOrCreate([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'subsystem_id' => $subsystemId,
        ], [
            'role_id' => $role->id,
        ]);
    }
}
