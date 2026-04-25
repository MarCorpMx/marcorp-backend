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
use App\Models\Role;
use App\Models\Branch;
use App\Models\BranchUserAccess;

class RootUserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Usuario ROOT
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
                'is_super_admin' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Organización ROOT
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
                    "number" => "7702021345",
                    "internationalNumber" => "+52 770 202 1345",
                    "nationalNumber" => "770 202 1345",
                    "e164Number" => "+527702021345",
                    "countryCode" => "MX",
                    "dialCode" => "+52"
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

                'timezone' => 'America/Mexico_City',

                'metadata' => [
                    'notes'    => 'Organización root del sistema',
                    'contact_success_message' => 'Tu mensaje fue recibido',
                ],
            ]
        );

        // Crear Branch si falla observer
        if ($organization->wasRecentlyCreated) {
            // ok, observer ya corrió
        } else {
            // asegurar branch principal
            \App\Models\Branch::firstOrCreate([
                'organization_id' => $organization->id,
                'is_primary' => true,
            ], [
                'name' => 'Sucursal Principal',
                'is_active' => true,
                'timezone' => $organization->timezone,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Usuario como OWNER / ROOT
        |--------------------------------------------------------------------------
        */
        OrganizationUser::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $user->id,
            ],
            [
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
        | Asignar TODOS los subsistemas con PLAN PRO
        |--------------------------------------------------------------------------
        */
        $subsystems = Subsystem::query()
            ->where('is_active', true)
            ->get();

        foreach ($subsystems as $subsystem) {
            /*
            |--------------------------------------------------------------------------
            | Obtener PLAN PRO
            |--------------------------------------------------------------------------
            */
            $proPlan = Plan::where('subsystem_id', $subsystem->id)
                ->where('key', 'premium')
                ->first();

            if (!$proPlan) {
                throw new \Exception("Plan PREMIUM no encontrado para subsystem {$subsystem->key}");
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


            // Acceso a sucursal
            $this->assignBranchAccess(
                $user->id,
                $organization->id,
                $subsystem->id,
                'root'
            );
        }
    }

   

    private function assignBranchAccess($userId, $organizationId, $subsystemId, $roleKey)
    {
        $branch = Branch::where('organization_id', $organizationId)
            ->where('is_primary', true)
            ->firstOrFail();

        $role = Role::where('key', $roleKey)->firstOrFail();

        BranchUserAccess::updateOrCreate([
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'branch_id' => $branch->id,
            'subsystem_id' => $subsystemId,
            'role_id' => $role->id,
        ], [
            'is_active' => true,
        ]);
    }
}
