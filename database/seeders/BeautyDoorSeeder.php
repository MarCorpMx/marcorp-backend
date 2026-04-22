<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\OrganizationSubsystem;
use App\Models\Subsystem;
use App\Models\Plan;

use App\Models\UserSubsystemRole;
use App\Models\Role;

class BeautyDoorSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | 1️⃣ Usuario Natalie
            |--------------------------------------------------------------------------
            */

            $natalie = User::firstOrCreate(
                ['email' => 'beautydoor@gmail.com'],
                [
                    'username'       => 'nat_admin',
                    'name'           => 'Nathaliie Sothelo',
                    'first_name'     => 'Nathaliie',
                    'last_name'      => 'Sothelo',
                    'password'       => Hash::make('Admin@123456'),
                    'status'         => 'active',
                    'email_verified' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ Organización Beauty Door
            |--------------------------------------------------------------------------
            */

            $organization = Organization::updateOrCreate(
                ['slug' => 'beauty-door'],
                [
                    'name' => 'Beauty Door',
                    'reference_prefix' => 'BD',
                    'type' => 'client',
                    'owner_user_id' => $natalie->id,
                    'status' => 'active',
                    'email' => 'beautydoor@gmail.com',
                    'country' => 'MX',
                    'metadata' => [
                        'timezone' => 'UTC',
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ Relación usuario-organización
            |--------------------------------------------------------------------------
            */

            OrganizationUser::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $natalie->id,
                ],
                [
                    'status' => 'active',
                    'joined_at' => now(),
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ Activar subsistema citas
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
                    'expires_at' => now()->addMonth(),
                    'is_paid'    => true,
                ]
            );

            // Creamos su rol (owner)
            $this->assignRole(
                $natalie->id,
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
