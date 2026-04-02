<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Organization;
use App\Models\StaffMember;

class BeautyDoorStaffSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $organization = Organization::where('slug', 'beauty-door')->first();

            if (!$organization) return;

            $staff = [
                [
                    'name' => 'Margarita Escobar',
                    'email' => 'margarita@beautydoor.com',
                ],
                [
                    'name' => 'María Martínez',
                    'email' => 'maria@beautydoor.com',
                ],
            ];

            foreach ($staff as $member) {
                StaffMember::updateOrCreate(
                    [
                        'organization_id' => $organization->id,
                        'email' => $member['email'],
                    ],
                    [
                        'name' => $member['name'],
                        'is_active' => 1,
                    ]
                );
            }
        });
    }
}
