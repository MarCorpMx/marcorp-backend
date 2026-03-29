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
            ClientUserSeeder::class, // Crea Punto de Calma
            //ClientSeeder::class,

            // Asignación de subsistemas
            OrganizationSubsystemSeeder::class,

            // Para Mails
            OrganizationMailSettingsSeeder::class,
            OrganizationMailTemplatesSeeder::class,

            // Punto-de-Calma -> Configuraciones
            PuntoDeCalmaServicesSeeder::class, // Crea los servicios principales (se puede dejar para que al inciar ya tenga servicios)
            //PuntoDeCalmaDemoSeeder::class, // Crea clientes, citas

            // Crear Reglas de Notificaciones
            NotificationRuleSeeder::class,
            

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
