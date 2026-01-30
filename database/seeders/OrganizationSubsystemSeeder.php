<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Subsystem;
use App\Models\OrganizationSubsystem;

class OrganizationSubsystemSeeder extends Seeder
{
    public function run()
    {
        $organizations = Organization::all();
        $subsystems = Subsystem::where('is_active', 1)->get();

        foreach ($organizations as $organization) {
            foreach ($subsystems as $subsystem) {

                OrganizationSubsystem::firstOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'subsystem_id' => $subsystem->id,
                    ],
                    [
                        'is_paid' => false,   // luego decides si se paga
                        'status' => 'active',
                    ]
                );

            }
        }
    }
}
