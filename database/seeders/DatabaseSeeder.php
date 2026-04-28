<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Catálogos
            SubsystemSeeder::class,
            RoleSeeder::class,
            PlanSeeder::class,
            FeatureSeeder::class,
            PlanSubsystemFeatureSeeder::class,

            // Usuarios + organizaciones
            //RootUserSeeder::class, // Crea MarCorp
            

            // Punto de Calma (se necesitan todos los de este bloque)
            PuntoDeCalmaOrganizationSeeder::class, // Crea al usuario Michelle con la organización PDC
            PuntoDeCalmaBranchesSeeder::class, // Crea Sucursales (sin permisos)
            PuntoDeCalmaBranchAccessSeeder::class, // Permisos a sucursales
            PuntoDeCalmaServicesSeeder::class, // Crea los servicios principales (se puede dejar para que al inciar ya tenga servicios)

            // BeautyDoor
            //BeautyDoorSeeder::class,
            //BeautyDoorStaffSeeder::class,
            //BeautyDoorServicesSeeder::class,


            // Asignación de subsistemas
            //OrganizationSubsystemSeeder::class, // Activa automáticamente el módulo WEB en plan FREE para todos los clientes

            // Proveedores de envio de correo
            OrganizationMailSettingsSeeder::class, // Configuracion de proveedores de envio - depende de tener creadas las organizaciones 
            
            // Templates
            CitaraMailTemplatesSeeder::class,
            //GeneralMailTemplatesSeeder::class, // este es bueno (crea los templates generales)
            //PuntoDeCalmaMailTemplatesSeeder::class, // Necesita a la organizacion de Punto de Calma

            // Punto-de-Calma -> Configuraciones
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
