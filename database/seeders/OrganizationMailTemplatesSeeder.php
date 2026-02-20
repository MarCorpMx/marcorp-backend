<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\OrganizationMailTemplate;

class OrganizationMailTemplatesSeeder extends Seeder
{
  public function run(): void
  {

  // Template generales
    OrganizationMailTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'contact_internal_notification'
      ],
      [
        'name' => 'Default Internal Contact Notification',
        'subject' => 'Nuevo mensaje desde {{organization_name}}',
        'body_html' => '
            <h2>Nuevo mensaje recibido</h2>
            <p><strong>Organización:</strong> {{organization_name}}</p>
            <p><strong>Nombre:</strong> {{first_name}} {{last_name}}</p>
            <p><strong>Email:</strong> {{email}}</p>
            <p><strong>Asunto:</strong> {{subject}}</p>
            <p><strong>Servicios:</strong> {{services}}</p>
            <hr>
            <p><strong>Mensaje:</strong></p>
            <p>{{message}}</p>
        ',
        'body_text' => '
Nuevo mensaje recibido

Organización: {{organization_name}}
Nombre: {{first_name}} {{last_name}}
Email: {{email}}
Asunto: {{subject}}
Servicios: {{services}}

Mensaje:
{{message}}
        ',
        'is_active' => true,
      ]
    );

    /*
        |--------------------------------------------------------------------------
        | 🏢 MarCorp Templates
        |--------------------------------------------------------------------------
        */
    $marcorp = Organization::where('slug', 'marcorp')->first();

    if ($marcorp) {

      OrganizationMailTemplate::updateOrCreate(
        [
          'organization_id' => $marcorp->id,
          'type' => 'contact_auto_reply'
        ],
        [
          'name' => 'Auto respuesta contacto',
          'subject' => 'Gracias por contactar a MarCorp 🚀',
          'body_html' => '
                        <h2>Hola {{name}},</h2>
                        <p>Gracias por comunicarte con MarCorp.</p>
                        <p>En breve uno de nuestros especialistas se pondrá en contacto contigo.</p>
                        <br>
                        <p><strong>Equipo MarCorp</strong></p>
                    ',
          'body_text' => 'Hola {{name}}, gracias por contactar a MarCorp.',
          'is_active' => true,
        ]
      );
    }

    /*
        |--------------------------------------------------------------------------
        | 🌿 Punto de Calma Templates
        |--------------------------------------------------------------------------
        */
    $pdc = Organization::where('slug', 'punto-de-calma')->first();

    if ($pdc) {

      OrganizationMailTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'contact_auto_reply'
        ],
        [
          'name' => 'Auto respuesta contacto',
          'subject' => 'Gracias por escribir a Punto de Calma 🌿',
          'body_html' => '
                        <!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Gracias por escribir</title>
</head>
<body style="margin:0;padding:0;background-color:#EFEDEA;font-family:Arial, Helvetica, sans-serif;">

  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
    <tr>
      <td align="center">

        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;padding:40px;box-shadow:0 8px 24px rgba(0,0,0,0.05);">

          <tr>
            <td align="center" style="padding-bottom:20px;">
              <div style="width:40px;height:4px;background:#8B907E;border-radius:4px;margin-bottom:20px;"></div>
              <h2 style="color:#6F6F6F;margin:0;font-weight:500;">
                Hola {{first_name}},
              </h2>
            </td>
          </tr>

          <tr>
            <td style="color:#6F6F6F;font-size:16px;line-height:1.6;">
              <p>
                Gracias por tomarte este momento para escribir.
              </p>

              <p>
                He recibido tu mensaje y lo leeré con atención y cuidado.
                Te responderé personalmente dentro de las próximas 24 horas.
              </p>

              <p>
                Mientras tanto, puedes darte permiso de estar con lo que estés sintiendo.
              </p>

              <br>

              <p style="margin-bottom:0;">
                Con cariño,
              </p>

              <p style="margin-top:4px;font-weight:bold;color:#8B907E;">
                Michelle
              </p>
			  <p style="margin-top:4px;font-weight:bold;color:#8B907E;">
                Equipo Punto de Calma
              </p>

              <hr style="border:none;border-top:1px solid #EEE6DC;margin:30px 0;">

              <p style="font-size:12px;color:#9a9a9a;">
                Este mensaje confirma que tu correo fue recibido correctamente.
                Si no solicitaste este contacto, puedes ignorarlo.
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>
                    ',
          'body_text' => 'Hola {{first_name}}, gracias por escribir a Punto de Calma.',
          'is_active' => true,
        ]
      );
    }
  }
}
