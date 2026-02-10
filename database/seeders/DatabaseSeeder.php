<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubsystemSeeder::class,
            RoleSeeder::class,
            PlanSeeder::class,
            FeatureSeeder::class,
            PlanSubsystemFeatureSeeder::class,
            RootUserSeeder::class,
            OrganizationSeeder::class,
            ClientSeeder::class,
            OrganizationUserSeeder::class,
            OrganizationSubsystemSeeder::class
        ]);
    }
}
