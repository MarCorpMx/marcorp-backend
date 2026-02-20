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
        | 🏢 Marcorp
        |--------------------------------------------------------------------------
        */
        $marcorp = Organization::where('slug', 'marcorp')->first();

        if ($marcorp) {

            // 🔹 SES 
            OrganizationMailSetting::updateOrCreate(
                [
                    'organization_id' => $marcorp->id,
                    'provider' => 'ses',
                ],
                [
                    'mailer'       => 'smtp',
                    'host'         => 'email-smtp.us-east-1.amazonaws.com',
                    'port'         => 587,
                    'username'     => env('SES_SMTP_USERNAME_MARCORP'),
                    'password'     => env('SES_SMTP_PASSWORD_MARCORP'),
                    'encryption'   => 'tls',
                    'from_address' => 'soporte@marcorp.mx',
                    'from_name'    => 'MarCorp',
                    'is_active'    => false,
                    'priority'     => 1,
                ]
            );

            // 🔹 SendGrid 
            OrganizationMailSetting::updateOrCreate(
                [
                    'organization_id' => $marcorp->id,
                    'provider' => 'sendgrid',
                ],
                [
                    'mailer'       => 'smtp',
                    'host'         => 'smtp.sendgrid.net',
                    'port'         => 587,
                    'username'     => 'apikey',
                    'password'     => env('SENDGRID_API_KEY_MARCORP'),
                    'encryption'   => 'tls',
                    'from_address' => 'no-reply@mail.marcorp.mx',
                    'from_name'    => 'MarCorp',
                    'is_active'    => true,
                    'priority'     => 2,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 🌿 Punto de Calma
        |--------------------------------------------------------------------------
        */
        $pdc = Organization::where('slug', 'punto-de-calma')->first();

        if ($pdc) {

            // 🔹 SES (ACTIVO)
            OrganizationMailSetting::updateOrCreate(
                [
                    'organization_id' => $pdc->id,
                    'provider' => 'ses',
                ],
                [
                    'mailer'       => 'smtp',
                    'host'         => 'email-smtp.us-east-1.amazonaws.com',
                    'port'         => 587,
                    'username'     => env('SES_SMTP_USERNAME_PDC'),
                    'password'     => env('SES_SMTP_PASSWORD_PDC'),
                    'encryption'   => 'tls',
                    'from_address' => 'contacto@punto-de-calma.com',
                    'from_name'    => 'Punto de Calma',
                    'is_active'    => false,
                    'priority'     => 1,
                ]
            );

            // 🔹 SendGrid 
            OrganizationMailSetting::updateOrCreate(
                [
                    'organization_id' => $pdc->id,
                    'provider' => 'sendgrid',
                ],
                [
                    'mailer'       => 'smtp',
                    'host'         => 'smtp.sendgrid.net',
                    'port'         => 587,
                    'username'     => 'apikey',
                    'password'     => env('SENDGRID_API_KEY_PDC'),
                    'encryption'   => 'tls',
                    'from_address' => 'no-reply@mail.punto-de-calma.com',
                    'from_name'    => 'Punto de Calma',
                    'is_active'    => true,
                    'priority'     => 2,
                ]
            );
        }
    }
}
