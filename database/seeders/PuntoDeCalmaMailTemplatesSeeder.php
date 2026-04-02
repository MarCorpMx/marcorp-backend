<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\NotificationTemplate;

class PuntoDeCalmaMailTemplatesSeeder extends Seeder
{
  public function run(): void
  {
    /*
        |--------------------------------------------------------------------------
        | 🌿 Punto de Calma Templates
        |--------------------------------------------------------------------------
        */
    $pdc = Organization::where('slug', 'punto-de-calma')->first();

    if ($pdc) {

      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'contact_internal_notification',
          'channel' => 'email',
        ],
        [
          'name' => 'Nuevo mensaje recibido',
          'subject' => 'Nuevo mensaje recibido en Punto de Calma 🌿',
          'body' => '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Nuevo mensaje</title>
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
Nuevo mensaje recibido
</h2>

</td>
</tr>

<tr>
<td style="color:#6F6F6F;font-size:15px;line-height:1.6;">

<p>
Has recibido un nuevo mensaje a través de Punto de Calma.
</p>

<br>

<table width="100%" cellpadding="8" cellspacing="0" style="background:#F7F5F2;border-radius:12px;">
<tr>
<td>

<p><strong>Nombre:</strong><br>{{first_name}} {{last_name}}</p>

<p><strong>Email:</strong><br>{{email}}</p>

{{#subject}}
<p><strong>Asunto:</strong><br>{{subject}}</p>
{{/subject}}

{{#services}}
<p><strong>Interés en:</strong><br>{{services}}</p>
{{/services}}

</td>
</tr>
</table>

<br>

<p><strong>Mensaje:</strong></p>

<div style="background:#FBFAF8;padding:16px;border-radius:12px;color:#555;">
{{message}}
</div>

<br>

<p style="font-size:12px;color:#9a9a9a;">
Recuerda responder desde un espacio tranquilo y presente.
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
          'body_text' => '
Nuevo mensaje recibido

Nombre: {{first_name}} {{last_name}}
Email: {{email}}
Asunto: {{subject}}
Servicios: {{services}}

Mensaje:
{{message}}
',
          'is_active' => true,
        ]
      ); // Fin template


      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'contact_auto_reply',
          'channel' => 'email',
        ],
        [
          'name' => 'Auto respuesta contacto',
          'subject' => 'Gracias por escribir a Punto de Calma 🌿',
          'body' => '
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


      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_request_received',
          'channel' => 'email',
        ],
        [
          'name' => 'Confirmación solicitud de cita',
          'subject' => 'Hemos recibido tu solicitud de cita 🌿',
          'body' => '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Solicitud de cita recibida</title>
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
Gracias por solicitar una cita en <strong>Punto de Calma</strong>.
</p>

<p>
He recibido tu solicitud para el siguiente espacio:
</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}
</p>

<p style="font-size:12px; color:#666;">
          ID de referencia: <strong>{{reference_code}}</strong><br>
          Guarda este código por si necesitas soporte o cambios.
</p>

{{#manage_url}}
<br><br>

<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center">

      <a href="{{manage_url}}"
         style="
           display:inline-block;
           padding:14px 26px;
           background:#8B907E;
           color:#ffffff;
           text-decoration:none;
           border-radius:30px;
           font-size:14px;
           font-weight:500;
           letter-spacing:0.3px;
         ">
        Gestionar mi cita
      </a>

      <p style="font-size:12px;color:#9a9a9a;margin-top:12px;">
        Cambiar horario o cancelar fácilmente
      </p>

    </td>
  </tr>
</table>

{{/manage_url}}

<p>
Revisaré personalmente la disponibilidad y te confirmaré muy pronto.
</p>

<p>
Mientras tanto, puedes darte este momento para respirar y estar contigo.
</p>

<br>

<p style="margin-bottom:0;">Con cariño,</p>

<p style="margin-top:4px;font-weight:bold;color:#8B907E;">
Michelle
</p>

<p style="margin-top:4px;font-weight:bold;color:#8B907E;">
Equipo Punto de Calma
</p>

<hr style="border:none;border-top:1px solid #EEE6DC;margin:30px 0;">

<p style="font-size:12px;color:#9a9a9a;">
Este mensaje confirma que tu solicitud fue recibida correctamente.
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
          'body_text' => 'Hola {{first_name}}, hemos recibido tu solicitud de cita en Punto de Calma.',
          'is_active' => true,
        ]
      ); // Fin template


      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_internal_notification',
          'channel' => 'email',
        ],
        [
          'name' => 'Nueva solicitud de cita',
          'subject' => 'Nueva solicitud de cita en Punto de Calma',
          'body' => '
<h2>Nueva solicitud de cita</h2>

<p><strong>Cliente:</strong> {{first_name}} {{last_name}}</p>
<p><strong>Email:</strong> {{email}}</p>
<p><strong>Teléfono:</strong> {{phone}}</p>

<p><strong>Servicio:</strong> {{service_name}}</p>
<p><strong>Modalidad:</strong> {{mode}}</p>
<p><strong>Fecha:</strong> {{date}}</p>
<p><strong>Hora:</strong> {{time}}</p>
<p><strong>Notas del cliente:</strong> {{notes}}</p>
<p><strong>Organización:</strong> {{organization_name}}</p>

<p style="font-size:12px; color:#666;">
          ID de referencia: <strong>{{reference_code}}</strong><br>
          Guarda este código por si necesitas soporte o cambios.
</p>


{{#confirm_url}}
<br><br>
<a href="{{confirm_url}}" style="display:inline-block;padding:10px 18px;background:#8B907E;color:white;text-decoration:none;border-radius:20px;">
Confirmar cita
</a>
{{/confirm_url}}

{{#cancel_url}}
<a href="{{cancel_url}}" style="display:inline-block;padding:10px 18px;background:#D6CFC7;color:#6F6F6F;text-decoration:none;border-radius:20px;margin-left:10px;">
Cancelar
</a>
{{/cancel_url}}

{{#pro_tip}}
<p style="font-size:12px;color:#9a9a9a;margin-top:20px;">
Puedes gestionar tu cita directamente desde este correo en una versión más completa del servicio.
</p>
{{/pro_tip}}

',
          'body_text' => '
Nueva solicitud de cita

Cliente: {{first_name}} {{last_name}}
Email: {{email}}
Teléfono: {{phone}}

Servicio: {{service_name}}
Modalidad: {{mode}}
Fecha: {{date}}
Hora: {{time}}
Notas del cliente: {{notes}}
Ref: {{reference_code}}
',
          'is_active' => true,
        ]
      ); // Fin template


      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_confirmed',
          'channel' => 'email',
        ],
        [
          'name' => 'Cita confirmada',
          'subject' => 'Tu espacio ha sido confirmado 🌿',
          'body' => '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Cita confirmada</title>
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
Tu espacio ha sido confirmado 🌿
</p>

<p>
Te estaré acompañando en el siguiente momento:
</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}
</p>

<p style="font-size:12px; color:#666;">
ID de referencia: <strong>{{reference_code}}</strong>
</p>

{{#manage_url}}
<br><br>

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<a href="{{manage_url}}"
   style="
     display:inline-block;
     padding:14px 26px;
     background:#8B907E;
     color:#ffffff;
     text-decoration:none;
     border-radius:30px;
     font-size:14px;
     font-weight:500;
   ">
Gestionar mi cita
</a>

<p style="font-size:12px;color:#9a9a9a;margin-top:12px;">
Si necesitas cambiar o cancelar, puedes hacerlo fácilmente
</p>

</td>
</tr>
</table>

{{/manage_url}}

<p>
Puedes darte este momento como un pequeño espacio para ti desde ahora.
</p>

<br>

<p>Con cariño,</p>

<p style="font-weight:bold;color:#8B907E;">Michelle</p>
<p style="font-weight:bold;color:#8B907E;">Equipo Punto de Calma</p>

<hr style="border:none;border-top:1px solid #EEE6DC;margin:30px 0;">

<p style="font-size:12px;color:#9a9a9a;">
Este mensaje confirma que tu cita ha sido agendada correctamente.
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
          'body_text' => 'Hola {{first_name}}, tu cita ha sido confirmada en Punto de Calma.',
          'is_active' => true,
        ]
      ); // Fin template


      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_cancelled',
          'channel' => 'email',
        ],
        [
          'name' => 'Cita cancelada',
          'subject' => 'Tu cita ha sido cancelada',
          'body' => '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Cita cancelada</title>
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
Tu cita ha sido cancelada.
</p>

<p>
Si en algún momento sientes que quieres retomar este espacio, puedes agendar nuevamente:
</p>

<br>

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<a href="{{booking_url}}"
   style="
     display:inline-block;
     padding:14px 26px;
     background:#8B907E;
     color:#ffffff;
     text-decoration:none;
     border-radius:30px;
     font-size:14px;
     font-weight:500;
   ">
Agendar nueva cita
</a>

</td>
</tr>
</table>

<br>

<p>
Estoy aquí cuando lo necesites.
</p>

<br>

<p>Con cariño,</p>

<p style="font-weight:bold;color:#8B907E;">Michelle</p>
<p style="font-weight:bold;color:#8B907E;">Equipo Punto de Calma</p>

<hr style="border:none;border-top:1px solid #EEE6DC;margin:30px 0;">

<p style="font-size:12px;color:#9a9a9a;">
Si no realizaste esta acción, puedes ignorar este mensaje.
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
          'body_text' => 'Hola {{first_name}}, tu cita ha sido cancelada.',
          'is_active' => true,
        ]
      ); // Fin template

      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_client_cancelled',
          'channel' => 'email',
        ],
        [
          'name' => 'Cliente canceló su cita',
          'subject' => 'Una persona ha cancelado su espacio 🌿',
          'body' => '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Cita cancelada</title>
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
Espacio cancelado
</h2>

</td>
</tr>

<tr>
<td style="color:#6F6F6F;font-size:15px;line-height:1.6;">

<p>
Una persona ha decidido cancelar su espacio.
</p>

<br>

<table width="100%" cellpadding="10" cellspacing="0" style="background:#F7F5F2;border-radius:12px;">
<tr>
<td>

<p><strong>Nombre:</strong><br>{{first_name}} {{last_name}}</p>

<p><strong>Email:</strong><br>{{email}}</p>

<p><strong>Servicio:</strong><br>{{service_name}}</p>

<p><strong>Fecha original:</strong><br>{{date}} a las {{time}}</p>

</td>
</tr>
</table>

{{#note}}
<br>

<p><strong>Mensaje de la persona:</strong></p>

<div style="background:#FBFAF8;padding:16px;border-radius:12px;color:#555;">
{{note}}
</div>
{{/note}}

<br>

<p style="font-size:12px;color:#9a9a9a;">
Referencia: {{reference_code}}
</p>

<p style="font-size:12px;color:#9a9a9a;">
Este espacio ahora queda disponible para alguien más.
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
          'body_text' => '
Cita cancelada

Cliente: {{first_name}} {{last_name}}
Email: {{email}}

Servicio: {{service_name}}
Fecha: {{date}} {{time}}

Nota:
{{note}}

Referencia: {{reference_code}}
',
          'is_active' => true,
        ]
      ); // Fin template

      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_rescheduled',
          'channel' => 'email',
        ],
        [
          'name' => 'Cita reagendada',
          'subject' => 'Tu nuevo espacio ha sido reservado 🌿',
          'body' => '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Cita reagendada</title>
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
Tu nuevo espacio ha sido reservado 🌿
</p>

<p>
Hemos actualizado tu cita al siguiente momento:
</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}
</p>

{{#old_date}}
<p style="font-size:12px;color:#9a9a9a;">
Antes estaba programada para: {{old_date}}
</p>
{{/old_date}}

{{#manage_url}}
<br><br>

<table width="100%">
<tr>
<td align="center">

<a href="{{manage_url}}"
   style="
     display:inline-block;
     padding:14px 26px;
     background:#8B907E;
     color:#ffffff;
     text-decoration:none;
     border-radius:30px;
     font-size:14px;
     font-weight:500;
   ">
Gestionar mi cita
</a>

<p style="font-size:12px;color:#9a9a9a;margin-top:12px;">
Si necesitas ajustar algo, puedes hacerlo fácilmente
</p>

</td>
</tr>
</table>

{{/manage_url}}

<p>
Este espacio sigue siendo completamente tuyo.
</p>

<br>

<p>Con cariño,</p>

<p style="font-weight:bold;color:#8B907E;">Michelle</p>
<p style="font-weight:bold;color:#8B907E;">Equipo Punto de Calma</p>

<hr style="border:none;border-top:1px solid #EEE6DC;margin:30px 0;">

<p style="font-size:12px;color:#9a9a9a;">
Este mensaje confirma que tu cita fue actualizada correctamente.
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
          'body_text' => 'Tu cita ha sido reagendada en Punto de Calma',
          'is_active' => true,
        ]
      ); // Fin template - Cliente

      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_client_rescheduled',
          'channel' => 'email',
        ],
        [
          'name' => 'Cliente reagendó cita',
          'subject' => 'Un cliente actualizó su cita 🌿',
          'body' => '
<h2>Cita actualizada por el cliente</h2>

<p><strong>Cliente:</strong> {{first_name}} {{last_name}}</p>
<p><strong>Email:</strong> {{email}}</p>

<p><strong>Servicio:</strong> {{service_name}}</p>

<p><strong>Antes:</strong> {{old_date}}</p>
<p><strong>Ahora:</strong> {{new_date}}</p>

{{#note}}
<hr>
<p><strong>Nota del cliente:</strong></p>
<p>{{note}}</p>
{{/note}}

<p><strong>Referencia:</strong> {{reference_code}}</p>

{{#confirm_url}}
<br><br>
<a href="{{confirm_url}}" style="display:inline-block;padding:10px 18px;background:#8B907E;color:white;text-decoration:none;border-radius:20px;">
Confirmar nuevo horario
</a>
{{/confirm_url}}

{{#cancel_url}}
<a href="{{cancel_url}}" style="display:inline-block;padding:10px 18px;background:#D6CFC7;color:#6F6F6F;text-decoration:none;border-radius:20px;margin-left:10px;">
Cancelar
</a>
{{/cancel_url}}
',
          'body_text' => 'Cliente reagendó su cita',
          'is_active' => true,
        ]
      ); // Fin template - Interno

      NotificationTemplate::updateOrCreate(
        [
          'organization_id' => $pdc->id,
          'type' => 'appointment_created_by_staff',
          'channel' => 'email',
        ],
        [
          'name' => 'Cita agendada por Punto de Calma',
          'subject' => 'He reservado un espacio para ti 🌿',
          'body' => '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Cita agendada</title>
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
Quería contarte que he reservado un espacio para ti en <strong>Punto de Calma</strong>.
</p>

<p>
Este será tu momento:
</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}
</p>

<p style="font-size:12px; color:#666;">
ID de referencia: <strong>{{reference_code}}</strong><br>
Guárdalo por si necesitas hacer algún ajuste.
</p>

{{#manage_url}}
<br><br>

<table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td align="center">

      <a href="{{manage_url}}"
         style="
           display:inline-block;
           padding:14px 26px;
           background:#8B907E;
           color:#ffffff;
           text-decoration:none;
           border-radius:30px;
           font-size:14px;
           font-weight:500;
           letter-spacing:0.3px;
         ">
        Gestionar mi cita
      </a>

      <p style="font-size:12px;color:#9a9a9a;margin-top:12px;">
        Si necesitas cambiar el horario o cancelar, puedes hacerlo fácilmente aquí
      </p>

    </td>
  </tr>
</table>

{{/manage_url}}

<p>
Si algo no te funciona con el horario, con confianza lo ajustamos.
</p>

<p>
Estoy aquí para acompañarte en este proceso.
</p>

<br>

<p style="margin-bottom:0;">Con cariño,</p>

<p style="margin-top:4px;font-weight:bold;color:#8B907E;">
Michelle
</p>

<p style="margin-top:4px;font-weight:bold;color:#8B907E;">
Equipo Punto de Calma
</p>

<hr style="border:none;border-top:1px solid #EEE6DC;margin:30px 0;">

<p style="font-size:12px;color:#9a9a9a;">
Este mensaje confirma que tu cita fue agendada.
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
          'body_text' => '
Hola {{first_name}},

He reservado un espacio para ti en Punto de Calma.

Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}

Ref: {{reference_code}}

Si necesitas ajustar algo, puedes gestionar tu cita aquí:
{{manage_url}}

Con cariño,
Michelle
        ',
          'is_active' => true,
        ]
      ); // Fin template



    } // fin de PDC
  }
}
