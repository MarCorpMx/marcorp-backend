<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\OrganizationSubsystem;
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
            ['email' => 'omar@marcorp.com'],
            [
                'username' => 'omar_root',
                'name' => 'Omar Antunez',
                'first_name' => 'Omar',
                'last_name' => 'Antunez',
                'password' => Hash::make('Root@123456'),
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
            ['owner_user_id' => $user->id],
            [
                'name' => 'Marcorp Root',
                'slug' => Str::slug('marcorp-root'),
                'status' => 'active',
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
        | 4️⃣ Obtener PLAN PRO
        |--------------------------------------------------------------------------
        */
        $proPlan = Plan::where('key', 'pro')->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | 5️⃣ Asignar TODOS los subsistemas con PLAN PRO
        |--------------------------------------------------------------------------
        */
        $subsystems = Subsystem::all();

        foreach ($subsystems as $subsystem) {
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
