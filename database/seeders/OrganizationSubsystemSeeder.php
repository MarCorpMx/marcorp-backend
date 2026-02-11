<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Subsystem;
use App\Models\OrganizationSubsystem;
use App\Models\Plan;

class OrganizationSubsystemSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Plan FREE
        |--------------------------------------------------------------------------
        */
        $freePlan = Plan::where('key', 'free')->first();

        if (!$freePlan) {
            $this->command->warn('Plan FREE no encontrado.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Subsystem WEB
        |--------------------------------------------------------------------------
        */
        $webSubsystem = Subsystem::where('key', 'web')->first();

        if (!$webSubsystem) {
            $this->command->warn('Subsystem WEB no encontrado.');
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Organizaciones CLIENTE (no root)
        |--------------------------------------------------------------------------
        */
        $clientOrganizations = Organization::where('type', 'client')->get();

        foreach ($clientOrganizations as $organization) {

            OrganizationSubsystem::firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'subsystem_id'    => $webSubsystem->id,
                ],
                [
                    'plan_id'   => $freePlan->id,
                    'status'    => 'active',
                    'is_paid'   => false,
                    'started_at'=> now(),
                ]
            );

        }
    }
}
