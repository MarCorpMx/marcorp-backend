<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\NotificationTemplate;

class GeneralMailTemplatesSeeder extends Seeder
{
  public function run(): void
  {
    // Template generales
    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'contact_auto_reply',
        'channel' => 'email',
      ],
      [
        'name' => 'Default Contact Auto Reply',
        'subject' => 'Gracias por contactar a {{organization_name}}',
        'body' => '
        <h2>Hola {{first_name}},</h2>

        <p>Gracias por ponerte en contacto con <strong>{{organization_name}}</strong>.</p>

        <p>Hemos recibido tu mensaje y nuestro equipo lo revisará a la brevedad.</p>

        {{#message}}
        <p style="margin-top:20px;"><strong>Tu mensaje:</strong></p>
        <p style="color:#555;">{{message}}</p>
        {{/message}}

        <p style="margin-top:20px;">
            Te responderemos lo antes posible.
        </p>

        <br>

        <p>Saludos,<br>
        <strong>Equipo {{organization_name}}</strong></p>

        <hr>

        <p style="font-size:12px;color:#888;">
            Este es un mensaje automático para confirmar la recepción de tu solicitud.
        </p>
    ',
        'body_text' => '
Hola {{first_name}},

Gracias por contactar a {{organization_name}}.

Hemos recibido tu mensaje y lo revisaremos pronto.

{{#message}}
Tu mensaje:
{{message}}
{{/message}}

Te responderemos lo antes posible.

Saludos,
Equipo {{organization_name}}
    ',
        'is_active' => true,
      ]
    ); // Fin template

    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'contact_internal_notification',
        'channel' => 'email',
      ],
      [
        'name' => 'Default Internal Contact Notification',
        'subject' => 'Nuevo mensaje desde {{organization_name}}',
        'body' => '
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
    ); // Fin template

    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_internal_notification',
        'channel' => 'email',
      ],
      [
        'name' => 'Default Appointment Internal Notification',
        'subject' => 'Nueva solicitud de cita en {{organization_name}}',
        'body' => '
        <h2>Nueva solicitud de cita</h2>

        <p><strong>Organización:</strong> {{organization_name}}</p>

        <p><strong>Cliente:</strong> {{first_name}} {{last_name}}</p>
        <p><strong>Email:</strong> {{email}}</p>
        <p><strong>Teléfono:</strong> {{phone}}</p>

        <hr>

        <p><strong>Servicio:</strong> {{service_name}}</p>
        <p><strong>Modalidad:</strong> {{mode}}</p>
        <p><strong>Fecha:</strong> {{date}}</p>
        <p><strong>Hora:</strong> {{time}}</p>
        <p><strong>Notas del cliente:</strong> {{notes}}</p>
        
        <hr>

        <p style="font-size:12px; color:#666;">
          ID de referencia: <strong>{{reference_code}}</strong><br>
          Guarda este código por si necesitas soporte o cambios.
        </p>

        {{#confirm_url}}
<br><br>
<a href="{{confirm_url}}" style="display:inline-block;padding:12px 20px;background:#28a745;color:#fff;text-decoration:none;border-radius:6px;">
Confirmar cita
</a>
{{/confirm_url}}

{{#cancel_url}}
<a href="{{cancel_url}}" style="display:inline-block;padding:12px 20px;background:#dc3545;color:#fff;text-decoration:none;border-radius:6px;margin-left:10px;">
Cancelar cita
</a>
{{/cancel_url}}

{{#pro_tip}}
<p style="font-size:12px;color:#888;margin-top:20px;">
{{pro_tip}}
</p>
{{/pro_tip}}

    ',
        'body_text' => '
Nueva solicitud de cita

Organización: {{organization_name}}

Cliente: {{first_name}} {{last_name}}
Email: {{email}}
Teléfono: {{phone}}

Servicio: {{service_name}}
Modalidad: {{mode}}
Fecha: {{date}}
Hora: {{time}}
Notas: {{notes}}
Ref: {{reference_code}}
    ',
        'is_active' => true,
      ]
    ); // Fin template

    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_request_received',
        'channel' => 'email',
      ],
      [
        'name' => 'Default Appointment Request Received',
        'subject' => 'Hemos recibido tu solicitud de cita',
        'body' => '
        <h2>Solicitud de cita recibida</h2>

        <p>Hola {{first_name}},</p>

        <p>Hemos recibido tu solicitud de cita.</p>

        <p><strong>Servicio:</strong> {{service_name}}</p>
        <p><strong>Fecha:</strong> {{date}}</p>
        <p><strong>Hora:</strong> {{time}}</p>

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

        <p>En breve confirmaremos tu cita.</p>

        <br>

        <p>{{organization_name}}</p>
    ',
        'body_text' => '
Solicitud de cita recibida

Hola {{first_name}}

Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}
Ref: {{reference_code}}

Pronto confirmaremos tu cita.
    ',
        'is_active' => true,
      ]
    ); // Fin template


    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_confirmed',
        'channel' => 'email',
      ],
      [
        'name' => 'Default Appointment Confirmed',
        'subject' => 'Tu cita ha sido confirmada',
        'body' => '
<h2>Cita confirmada</h2>

<p>Hola {{first_name}},</p>

<p>Tu cita ha sido confirmada con los siguientes detalles:</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}<br>
<strong>ID de referencia:</strong> {{reference_code}}
</p>

<p>Te esperamos. Si necesitas hacer cambios puedes hacerlo desde aquí:</p>

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

{{#pro_tip}}
<p style="font-size:12px;color:#888;margin-top:20px;">
{{pro_tip}}
</p>
{{/pro_tip}}

<br>
<p>{{organization_name}}</p>
',
        'body_text' => '
Cita confirmada

Hola {{first_name}}

Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}
Ref: {{reference_code}}
',
        'is_active' => true,
      ]
    ); // Fin template

    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_cancelled',
        'channel' => 'email',
      ],
      [
        'name' => 'Default Appointment Cancelled',
        'subject' => 'Tu cita ha sido cancelada',
        'body' => '
<h2>Cita cancelada</h2>

<p>Hola {{first_name}},</p>

<p>Tu cita ha sido cancelada.</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}<br>
<strong>ID de referencia:</strong> {{reference_code}}
</p>

<p>Si deseas agendar nuevamente, puedes hacerlo desde nuestra plataforma:</p>

<a href="{{booking_url}}" 
   style="display:inline-block;padding:10px 16px;background:#000;color:#fff;text-decoration:none;border-radius:6px;">
   Agendar nueva cita
</a>

<br>
<p>{{organization_name}}</p>
',
        'body_text' => '
Cita cancelada

Hola {{first_name}}

Tu cita ha sido cancelada.
',
        'is_active' => true,
      ]
    ); // Fin template


    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_client_cancelled',
        'channel' => 'email',
      ],
      [
        'name' => 'Cliente canceló cita',
        'subject' => 'Un cliente ha cancelado su cita',
        'body' => '
<h2>Cita cancelada por el cliente</h2>

<p><strong>Cliente:</strong> {{first_name}} {{last_name}}</p>
<p><strong>Email:</strong> {{email}}</p>

<p><strong>Servicio:</strong> {{service_name}}</p>
<p><strong>Fecha original:</strong> {{date}}</p>
<p><strong>Hora:</strong> {{time}}</p>

{{#note}}
<hr>
<p><strong>Nota del cliente:</strong></p>
<p>{{note}}</p>
{{/note}}

<p><strong>Referencia:</strong> {{reference_code}}</p>
',
        'body_text' => 'Cliente canceló su cita',
        'is_active' => true,
      ]
    ); // Fin template - Interno



    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_client_rescheduled',
        'channel' => 'email',
      ],
      [
        'name' => 'Cliente reagendó cita',
        'subject' => 'Un cliente ha reagendado su cita',
        'body' => '
<h2>Cita reagendada por el cliente</h2>

<p><strong>Cliente:</strong> {{first_name}} {{last_name}}</p>
<p><strong>Email:</strong> {{email}}</p>

<p><strong>Servicio:</strong> {{service_name}}</p>

<p><strong>Fecha anterior:</strong> {{old_date}}</p>
<p><strong>Nueva fecha:</strong> {{new_date}}</p>

{{#note}}
<hr>
<p><strong>Nota del cliente:</strong></p>
<p>{{note}}</p>
{{/note}}

<p><strong>Referencia:</strong> {{reference_code}}</p>

{{#confirm_url}}
<br><br>
<a href="{{confirm_url}}" style="display:inline-block;padding:10px 18px;background:#28a745;color:#fff;text-decoration:none;border-radius:6px;">
Confirmar nuevo horario
</a>
{{/confirm_url}}

{{#cancel_url}}
<a href="{{cancel_url}}" style="display:inline-block;padding:10px 18px;background:#dc3545;color:#fff;text-decoration:none;border-radius:6px;margin-left:10px;">
Cancelar cita
</a>
{{/cancel_url}}

',
        'body_text' => 'Cliente reagendó su cita',
        'is_active' => true,
      ]
    ); // Fin template - Interno

    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_rescheduled',
        'channel' => 'email',
      ],
      [
        'name' => 'Appointment Rescheduled',
        'subject' => 'Tu cita ha sido reagendada',
        'body' => '
<h2>Cita reagendada</h2>

<p>Hola {{first_name}},</p>

<p>Tu cita ha sido actualizada correctamente.</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Nueva fecha:</strong> {{date}}<br>
<strong>Nueva hora:</strong> {{time}}<br>
<strong>ID de referencia:</strong> {{reference_code}}
</p>

{{#old_date}}
<p style="color:#777;">
Fecha anterior: {{old_date}}
</p>
{{/old_date}}

<p>Puedes gestionar tu cita desde aquí:</p>

{{#manage_url}}
<br><br>

<a href="{{manage_url}}" 
   style="display:inline-block;padding:12px 20px;background:#000;color:#fff;text-decoration:none;border-radius:6px;">
   Gestionar mi cita
</a>

{{/manage_url}}

<br>
<p>{{organization_name}}</p>
',
        'body_text' => 'Tu cita fue reagendada',
        'is_active' => true,
      ]
    ); // Fin template - Interno

    NotificationTemplate::updateOrCreate(
      [
        'organization_id' => null,
        'type' => 'appointment_created_by_staff',
        'channel' => 'email',
      ],
      [
        'name' => 'Cita creada por el staff',
        'subject' => 'Tu cita ha sido agendada',
        'body' => '
<h2>Tu cita ha sido agendada</h2>

<p>Hola {{first_name}},</p>

<p>
El equipo de <strong>{{organization_name}}</strong> ha agendado una cita para ti.
</p>

<p><strong>Servicio:</strong> {{service_name}}</p>
<p><strong>Modalidad:</strong> {{mode}}</p>
<p><strong>Fecha:</strong> {{date}}</p>
<p><strong>Hora:</strong> {{time}}</p>

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
           letter-spacing:0.3px;
         ">
        Gestionar mi cita
      </a>

      <p style="font-size:12px;color:#9a9a9a;margin-top:12px;">
        Puedes reagendar o cancelar fácilmente
      </p>

    </td>
  </tr>
</table>

{{/manage_url}}

<br>

<p>Si tienes alguna duda, puedes responder a este correo.</p>

<p>{{organization_name}}</p>
        ',
        'body_text' => '
Tu cita ha sido agendada

Hola {{first_name}}

El equipo de {{organization_name}} ha agendado una cita para ti.

Servicio: {{service_name}}
Modalidad: {{mode}}
Fecha: {{date}}
Hora: {{time}}

Ref: {{reference_code}}

Puedes gestionar tu cita aquí:
{{manage_url}}
        ',
        'is_active' => true,
      ]
    ); // Fin template




  }
}
