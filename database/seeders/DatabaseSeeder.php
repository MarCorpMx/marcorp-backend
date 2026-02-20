<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Catálogos
            RoleSeeder::class,
            SubsystemSeeder::class,
            PlanSeeder::class,
            FeatureSeeder::class,
            PlanSubsystemFeatureSeeder::class,

            // Usuarios + organizaciones
            RootUserSeeder::class,
            ClientUserSeeder::class,
            //ClientSeeder::class,

            // Asignación de subsistemas
            OrganizationSubsystemSeeder::class,

            // Para Mails
            OrganizationMailSettingsSeeder::class,
            OrganizationMailTemplatesSeeder::class,
        ]);
    }
    /*
    public function run(): void
    {
        $this->call([
            -SubsystemSeeder::class,
            -RoleSeeder::class,
            -PlanSeeder::class,
            -FeatureSeeder::class,
            PlanSubsystemFeatureSeeder::class,
            RootUserSeeder::class,
            OrganizationSeeder::class,
            ClientSeeder::class,
            OrganizationUserSeeder::class, (FALTA)
            OrganizationSubsystemSeeder::class
        ]);
    }
        */
}
