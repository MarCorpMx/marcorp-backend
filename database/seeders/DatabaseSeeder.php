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
            MembershipSeeder::class,
            RootUserSeeder::class,
        ]);
    }
}
