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
use App\Models\Role;
use App\Models\Branch;
use App\Models\BranchUserAccess;
use App\Models\StaffMember;
use App\Models\BranchStaff;

class InternalOrganizationsSeeder extends Seeder
{

    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Usuario ROOT
        |--------------------------------------------------------------------------
        */

        $mailMarcorp = 'soporte@marcorp.mx';

        $userRoot = User::firstOrCreate(
            ['email' => $mailMarcorp],
            [
                'username' => 'omar_root',
                'name' => 'Omar Antunez',
                'first_name' => 'Omar',
                'last_name' => 'Antunez',
                'password' => Hash::make('mar011235@Rrom'),
                'status' => 'active',
                'is_super_admin' => true,

                'phone'          => [
                    "number" => "7702021345",
                    "internationalNumber" => "+52 770 202 1345",
                    "nationalNumber" => "770 202 1345",
                    "e164Number" => "+527702021345",
                    "countryCode" => "MX",
                    "dialCode" => "+52"
                ],
                'email_verified_at' => now(),

                'accepted_terms' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Marcorp && ROMBI
        |--------------------------------------------------------------------------
        */
        $organizations = [

            // MarCorp
            [
                'name' => 'MarCorp',
                'slug' => 'marcorp',

                'reference_prefix' => 'MAR',
                'type' => 'root',

                'slogan' => 'Soluciones en tecnología para el mundo moderno',
                'business_niche' => 'other',

                'email' => $mailMarcorp,

                'website' => 'https://www.marcorp.mx',

                'theme_key' => 'marcorp',
                'primary_color'   => '#18C48F',
                'secondary_color' => '#38BDF8',
                'logo_url'        => 'organizations/logos/marcorp-logo.jpg',

                'primary_domain' => 'marcorp.mx',
                'domains' => ['www.marcorp.mx'],

                'notes'    => 'Organización root del sistema',
                'contact_success_message' => 'Tu mensaje fue recibido',
            ],

            // ROMBI
            [
                'name' => 'ROMBI',
                'slug' => 'rombi',

                'reference_prefix' => 'ROM',
                'type' => 'root',

                'slogan' => 'La forma inteligente de agendar',
                'business_niche' => 'other',

                'email' => $mailMarcorp,

                'website' => 'https://agenda.marcorp.mx',

                'theme_key' => 'rombi',
                'primary_color'   => '#18C48F',
                'secondary_color' => '#38BDF8',
                'logo_url'        => 'organizations/logos/rombi-logo.jpg',

                'primary_domain' => 'marcorp.mx',
                'domains' => ['www.marcorp.mx'],

                'notes'    => 'Organización root del sistema',
                'contact_success_message' => 'Tu mensaje fue recibido',
            ],

        ];

        foreach ($organizations as $org) {

            $organization = Organization::updateOrCreate(
                ['slug' => $org['slug']],
                [
                    'created_by' => $userRoot->id,
                    'name'           => $org['name'],
                    'reference_prefix' => $org['reference_prefix'],
                    'type'           => 'root',
                    'is_internal'    => true,
                    'owner_user_id'  => $userRoot->id,
                    'status'         => 'active',

                    'slogan' => $org['slogan'],
                    'business_niche' => $org['business_niche'],

                    'onboarding_step' => 'completed',
                    'onboarding_completed_at' => now(),

                    'email'          => $org['email'],
                    'phone'          => [
                        "number" => "7702021345",
                        "internationalNumber" => "+52 770 202 1345",
                        "nationalNumber" => "770 202 1345",
                        "e164Number" => "+527702021345",
                        "countryCode" => "MX",
                        "dialCode" => "+52"
                    ],
                    'website' => $org['website'],

                    'country' => 'mx',
                    'state' => 'Morelos',
                    'city' => 'Jiutepec',
                    'zip_code' => '62564',
                    'address' => '',

                    'theme_key'      => $org['theme_key'],
                    'primary_color'  => $org['primary_color'],
                    'secondary_color' => $org['secondary_color'],
                    'logo_url'       => $org['logo_url'],
                    'white_label'    => false,
                    'primary_domain' => $org['primary_domain'],
                    'domains'        => $org['domains'],
                    'force_https'    => true,

                    'timezone' => 'America/Mexico_City',

                    'metadata' => [
                        'notes' => $org['notes'],
                        'contact_success_message' => $org['contact_success_message'],
                        'rating' => '4.9',
                        'reviews_count' => '1200',
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Asociar usuario como OWNER
            |--------------------------------------------------------------------------
            */
            OrganizationUser::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id'         => $userRoot->id,
                ],
                [
                    'status'    => 'active',
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
                        $mailMarcorp,
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
            | Asignar TODOS los subsistemas con PLAN PREMIUM
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

                /*
                |--------------------------------------------------------------------------
                | Crear staff para root
                |--------------------------------------------------------------------------
                */
                $ownerStaff = StaffMember::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'user_id' => $userRoot->id,
                    ],
                    [
                        'name' => $userRoot->name,
                        'email' => $userRoot->email,
                        'is_active' => true,
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Obtener sucursal principal
                |--------------------------------------------------------------------------
                */

                $primaryBranch = Branch::where('organization_id', $organization->id)
                    ->where('is_primary', true)
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Relación staff ↔ sucursal
                |--------------------------------------------------------------------------
                */

                BranchStaff::firstOrCreate([
                    'branch_id' => $primaryBranch->id,
                    'staff_member_id' => $ownerStaff->id,
                ]);

                /*
            |--------------------------------------------------------------------------
            | Acceso a sucursal (CON staff_member_id)
            |--------------------------------------------------------------------------
            */

                $role = Role::where('key', 'owner')->firstOrFail();

                BranchUserAccess::updateOrCreate([
                    'organization_id' => $organization->id,
                    'user_id' => $userRoot->id,
                    'branch_id' => $primaryBranch->id,
                    'subsystem_id' => $subsystem->id,
                ], [
                    'role_id' => $role->id,
                    'staff_member_id' => $ownerStaff->id,
                    'is_active' => true,
                ]);
            }
        }
    }
}
