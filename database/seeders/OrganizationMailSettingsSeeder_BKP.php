<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\OrganizationMailSetting;

class OrganizationMailSettingsSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 🏢 MarCorp Mail Config (Amazon SES)
        |--------------------------------------------------------------------------
        */
        $marcorp = Organization::where('slug', 'marcorp')->first();

        if ($marcorp) {
            OrganizationMailSetting::updateOrCreate(
                ['organization_id' => $marcorp->id],
                [
                    'mailer'       => 'smtp',
                    'host'         => 'email-smtp.us-east-1.amazonaws.com', // SES SMTP endpoint
                    'port'         => 587,
                    'username'     => env('SES_SMTP_USERNAME_MARCORP'), // generado en AWS
                    'password'     => env('SES_SMTP_PASSWORD_MARCORP'), // generado en AWS
                    'encryption'   => 'tls',
                    'from_address' => 'soporte@marcorp.mx',
                    'from_name'    => 'MarCorp',
                    'is_active'    => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 🌿 Punto de Calma Mail Config (Amazon SES)
        |--------------------------------------------------------------------------
        */
        $pdc = Organization::where('slug', 'punto-de-calma')->first();

        if ($pdc) {
            OrganizationMailSetting::updateOrCreate(
                ['organization_id' => $pdc->id],
                [
                    'mailer'       => 'smtp',
                    'host'         => 'email-smtp.us-east-1.amazonaws.com', // SES SMTP endpoint
                    'port'         => 587,
                    'username'     => env('SES_SMTP_USERNAME_PDC'), // generado en AWS
                    'password'     => env('SES_SMTP_PASSWORD_PDC'), // generado en AWS
                    'encryption'   => 'tls',
                    'from_address' => 'contacto@punto-de-calma.com',
                    'from_name'    => 'Punto de Calma',
                    'is_active'    => true,
                ]
            );
        }
    }
}
