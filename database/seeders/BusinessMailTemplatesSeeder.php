<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class BusinessMailTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Auto contestación de mensaje (página web)
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'contact_auto_reply',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

                'name' => 'Default Contact Auto Reply',

                'subject' => 'Gracias por contactar a {{organization_name}}',

                'body' => '
<h2>Hola {{first_name}},</h2>

<p>
Gracias por ponerte en contacto con <strong>{{organization_name}}</strong>.
</p>

<p>
Hemos recibido tu mensaje y nuestro equipo lo revisará a la brevedad.
</p>

{{#message}}
<p style="margin-top:20px;">
<strong>Tu mensaje:</strong>
</p>

<p style="color:#555;">
{{message}}
</p>
{{/message}}

<p style="margin-top:20px;">
Te responderemos lo antes posible.
</p>

<br>

<p>
Saludos,<br>
<strong>Equipo {{organization_name}}</strong>
</p>

<hr>

<p style="font-size:12px;color:#888;">
Este es un mensaje automático para confirmar la recepción de tu solicitud.
</p>
        ',

                'body_text' => '
Hola {{first_name}}

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

        /*
        |--------------------------------------------------------------------------
        | Notificación interna de correo (página web)
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'contact_internal_notification',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

                'name' => 'Default Internal Contact Notification',

                'subject' => 'Nuevo mensaje desde {{organization_name}}',

                'body' => '
<h2>Nuevo mensaje recibido</h2>

<p><strong>Organización:</strong> {{organization_name}}</p>

<p><strong>Nombre:</strong> {{first_name}} {{last_name}}</p>
<p><strong>Email:</strong> {{email}}</p>

{{#phone}}
<p><strong>Teléfono:</strong> {{phone}}</p>
{{/phone}}

<p><strong>Asunto:</strong> {{subject}}</p>

{{#services}}
<p><strong>Servicios:</strong> {{services}}</p>
{{/services}}

<hr>

<p><strong>Mensaje:</strong></p>

<p>
{{message}}
</p>
        ',

                'body_text' => '
Nuevo mensaje recibido

Organización: {{organization_name}}

Nombre: {{first_name}} {{last_name}}
Email: {{email}}

Teléfono: {{phone}}

Asunto: {{subject}}

Servicios: {{services}}

Mensaje:
{{message}}
        ',

                'is_active' => true,
            ]
        ); // Fin template

        /*
        |--------------------------------------------------------------------------
        | Cita - creada por Staff
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_created_by_staff',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',
                'name' => 'Cita creada por el staff',
                'subject' => 'Tu cita ha sido agendada',
                'body' => '
<h2 style="margin-bottom:8px;">
    Tu cita ha sido agendada
</h2>

<p style="color:#6b7280;margin-top:0;">
    Hola {{friendly_name}},
</p>

<p>
El equipo de <strong>{{organization_name}}</strong> ha agendado una cita para ti.
</p>

<!-- RESUMEN PRINCIPAL -->

<div
    style="
        background:#f9fafb;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:18px;
        margin:24px 0;
    "
>

<p><strong>Servicio:</strong> {{service_name}}</p>
<p><strong>Modalidad:</strong> {{mode}}</p>
<p><strong>Duración:</strong> {{duration_label}}</p>
<p><strong>Fecha:</strong> {{date}}</p>
<p><strong>Hora:</strong> {{time}}</p>

{{#staff_name}}

<p><strong>Profesional:</strong> {{staff_name}}</p>
{{/staff_name}}

</div>

<!-- REFERENCIA -->

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<div
    style="
        display:inline-block;
        padding:14px 28px;
        border-radius:999px;
        background:#f3f4f6;
        color:#111827;
        font-weight:700;
        font-size:16px;
        letter-spacing:.5px;
    "
>
    Ref: {{reference_code}}
</div>

<p
    style="
        font-size:12px;
        color:#6b7280;
        margin-top:10px;
    "
>
    Guarda este código para soporte, cambios o consultas.
</p>

{{#manage_url}}

<div
    style="
        background:#f9fafb;
        border:1px solid #e5e7eb;
        border-radius:12px;
        padding:18px;
        margin:24px 0;
        text-align:center;
    "
>

<p
    style="
        margin-top:0;
        color:#4b5563;
    "
>
    ¿Necesitas hacer algún cambio?
</p>

<a href="{{manage_url}}">
    Gestionar mi cita
</a>

<p
    style="
        font-size:12px;
        color:#9ca3af;
        margin-bottom:0;
    "
>
    Reagenda o cancela fácilmente cuando lo necesites.
</p>

</div>

{{/manage_url}}

</td>
</tr>
</table>

<!-- RESUMEN ECONÓMICO -->

<div
    style="
        background:#fafafa;
        border-radius:12px;
        padding:18px;
        margin:28px 0;
    "
>

<h3 style="margin-top:0;">
    Resumen
</h3>

<p><strong>Total:</strong> ${{final_price_formatted}}</p>

{{#deposit_amount}}

<p><strong>Anticipo:</strong> ${{deposit_amount}}</p>
{{/deposit_amount}}

</div>

{{#pet_name}}

<div
    style="
        background:#fafafa;
        border-radius:12px;
        padding:18px;
        margin:28px 0;
    "
>

<h3 style="margin-top:0;">
    Información de la mascota
</h3>

<p><strong>Nombre:</strong> {{pet_name}}</p>

{{#pet_species}}

<p><strong>Especie:</strong> {{pet_species}}</p>
{{/pet_species}}

{{#pet_breed}}

<p><strong>Raza:</strong> {{pet_breed}}</p>
{{/pet_breed}}

</div>

{{/pet_name}}

{{#meeting_url}}

<div
    style="
        background:#eff6ff;
        border-radius:12px;
        padding:20px;
        margin:28px 0;
        text-align:center;
    "
>

<h3 style="margin-top:0;">
    Sesión en línea
</h3>

<p style="color:#4b5563;">
    Utiliza el siguiente enlace para ingresar a tu sesión.
</p>

<a href="{{meeting_url}}"
style="
   display:inline-block;
   padding:14px 28px;
   background:#2563eb;
   color:#ffffff;
   text-decoration:none;
   border-radius:999px;
   font-size:14px;
   font-weight:600;
">
Ingresar a la sesión </a>

</div>

{{/meeting_url}}

{{#branch_address}}

<div
    style="
        background:#fafafa;
        border-radius:12px;
        padding:18px;
        margin:28px 0;
    "
>

<h3 style="margin-top:0;">
    Ubicación
</h3>

<p>
<strong>Sucursal:</strong> {{branch_name}}
</p>

<p>
{{branch_address}}
</p>

</div>

{{/branch_address}}

{{#directions_url}}

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<a href="{{directions_url}}"
style="
   display:inline-block;
   padding:14px 28px;
   background:#0f766e;
   color:#ffffff;
   text-decoration:none;
   border-radius:999px;
   font-size:14px;
   font-weight:600;
">
Cómo llegar </a>

</td>
</tr>
</table>

{{/directions_url}}

{{#maps_url}}

<p
    style="
        text-align:center;
        margin-top:12px;
    "
>
    <a
        href="{{maps_url}}"
        style="
            color:#6b7280;
            text-decoration:none;
            font-size:13px;
            font-weight:500;
        "
    >
        Ver ubicación en Google Maps →
    </a>
</p>

{{/maps_url}}

<p
    style="
        margin-top:32px;
        color:#6b7280;
        font-size:14px;
    "
>
    Conserva este correo para futuras consultas relacionadas con tu cita.
</p>
                ',

                'body_text' => '
Tu cita ha sido agendada

Hola {{friendly_name}}

El equipo de {{organization_name}} ha agendado una cita para ti.

DETALLES DE LA CITA

Servicio: {{service_name}}
Modalidad: {{mode}}
Duración: {{duration_label}}
Fecha: {{date}}
Hora: {{time}}

{{#staff_name}}
Profesional: {{staff_name}}
{{/staff_name}}

Referencia: {{reference_code}}

Guarda este código por si necesitas soporte o cambios.

{{#manage_url}}
GESTIONAR MI CITA

{{manage_url}}

Puedes reagendar o cancelar fácilmente.

{{/manage_url}}

RESUMEN

Total: ${{final_price_formatted}}

{{#deposit_amount}}
Anticipo: ${{deposit_amount}}
{{/deposit_amount}}

{{#pet_name}}
INFORMACIÓN DE LA MASCOTA

Nombre: {{pet_name}}

{{#pet_species}}
Especie: {{pet_species}}
{{/pet_species}}

{{#pet_breed}}
Raza: {{pet_breed}}
{{/pet_breed}}

{{/pet_name}}

{{#meeting_url}}
SESIÓN EN LÍNEA

Ingresa a tu sesión aquí:
{{meeting_url}}

{{/meeting_url}}

{{#branch_address}}
UBICACIÓN

Sucursal: {{branch_name}}

{{branch_address}}

{{/branch_address}}

{{#maps_url}}
Ver ubicación:
{{maps_url}}

{{/maps_url}}

{{#directions_url}}
Cómo llegar:
{{directions_url}}

{{/directions_url}}

Conserva este correo para futuras consultas relacionadas con tu cita.

        ',

                'is_active' => true,
            ]
        ); // Fin template

        /*
        |--------------------------------------------------------------------------
        | Cita - creada por cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_request_received',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

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

<p>
En breve confirmaremos tu cita.
</p>

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

        /*
        |--------------------------------------------------------------------------
        | Cita creada - Notificación interna
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_internal_notification',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

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

<a href="{{confirm_url}}"
   style="
      display:inline-block;
      padding:12px 20px;
      background:#28a745;
      color:#fff;
      text-decoration:none;
      border-radius:6px;
   ">
Confirmar cita
</a>

{{/confirm_url}}

{{#cancel_url}}

<a href="{{cancel_url}}"
   style="
      display:inline-block;
      padding:12px 20px;
      background:#dc3545;
      color:#fff;
      text-decoration:none;
      border-radius:6px;
      margin-left:10px;
   ">
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

        /*
        |--------------------------------------------------------------------------
        | Cita - Cancelación (cliente canceló cita) - Notificación cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_cancelled',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

                'name' => 'Default Appointment Cancelled',

                'subject' => 'Tu cita ha sido cancelada',

                'body' => '
<h2>Cita cancelada</h2>

<p>Hola {{first_name}},</p>

<p>
Tu cita ha sido cancelada.
</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}
</p>

<p style="font-size:12px;color:#666;">
ID de referencia: <strong>{{reference_code}}</strong>
</p>

{{#booking_url}}

<br>

<p>
Si deseas agendar nuevamente, puedes hacerlo desde aquí:
</p>

<p style="text-align:center;">

<a href="{{booking_url}}"
   style="
      display:inline-block;
      padding:14px 26px;
      background:#10b981;
      color:#ffffff;
      text-decoration:none;
      border-radius:8px;
      font-weight:600;
   ">
   Agendar nueva cita
</a>

</p>

{{/booking_url}}

<p>
Conserva este correo para futuras consultas relacionadas con tu cita.
</p>
        ',

                'body_text' => '
Cita cancelada

Hola {{first_name}}

Tu cita ha sido cancelada.

Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}

Referencia: {{reference_code}}

Puedes agendar nuevamente aquí:
{{booking_url}}
        ',

                'is_active' => true,
            ]
        ); // Fin template

        /*
        |--------------------------------------------------------------------------
        | Cita - Cancelación (cliente canceló cita) - Notificación interna
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_client_cancelled',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

                'name' => 'Cliente canceló cita',

                'subject' => 'Un cliente ha cancelado su cita',

                'body' => '
<h2>Cita cancelada por el cliente</h2>

<p><strong>Cliente:</strong> {{first_name}} {{last_name}}</p>
<p><strong>Email:</strong> {{email}}</p>

{{#phone}}
<p><strong>Teléfono:</strong> {{phone}}</p>
{{/phone}}

<hr>

<p><strong>Servicio:</strong> {{service_name}}</p>
<p><strong>Fecha original:</strong> {{date}}</p>
<p><strong>Hora:</strong> {{time}}</p>

{{#note}}
<hr>

<p><strong>Motivo o nota del cliente:</strong></p>

<p>{{note}}</p>
{{/note}}

<hr>

<p>
<strong>Referencia:</strong> {{reference_code}}
</p>
        ',

                'body_text' => '
Cita cancelada por el cliente

Cliente: {{first_name}} {{last_name}}
Email: {{email}}
Teléfono: {{phone}}

Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}

Nota:
{{note}}

Referencia: {{reference_code}}
        ',

                'is_active' => true,
            ]
        ); // Fin template

        /*
        |--------------------------------------------------------------------------
        | Cita - Confirmacion
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_confirmed',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

                'name' => 'Default Appointment Confirmed',

                'subject' => 'Tu cita ha sido confirmada',

                'body' => '
<h2>Cita confirmada</h2>

<p>Hola {{first_name}},</p>

<p>
Tu cita ha sido confirmada.
</p>

<p>
<strong>Servicio:</strong> {{service_name}}<br>
<strong>Fecha:</strong> {{date}}<br>
<strong>Hora:</strong> {{time}}
</p>

<p style="font-size:12px;color:#666;">
ID de referencia: <strong>{{reference_code}}</strong>
</p>

{{#manage_url}}

<br>

<p>
Si necesitas reagendar o cancelar tu cita puedes hacerlo aquí:
</p>

<p style="text-align:center;">

<a href="{{manage_url}}"
   style="
      display:inline-block;
      padding:14px 26px;
      background:#10b981;
      color:#ffffff;
      text-decoration:none;
      border-radius:8px;
      font-weight:600;
   ">
   Gestionar mi cita
</a>

</p>

<p style="font-size:12px;color:#888;text-align:center;">
Cambiar horario o cancelar fácilmente
</p>

{{/manage_url}}

{{#pro_tip}}

<hr>

<p style="font-size:13px;color:#666;">
{{pro_tip}}
</p>

{{/pro_tip}}

<p>
Te esperamos.
</p>
        ',

                'body_text' => '
Cita confirmada

Hola {{first_name}}

Tu cita ha sido confirmada.

Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}

Referencia: {{reference_code}}

Gestionar cita:
{{manage_url}}

{{pro_tip}}
        ',

                'is_active' => true,
            ]
        ); // Fin template


        /*
        |--------------------------------------------------------------------------
        | Cita - Reagenda Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_rescheduled',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

                'name' => 'Appointment Rescheduled',

                'subject' => 'Tu cita ha sido reagendada',

                'body' => '
<h2 style="margin-top:0;">
    Tu cita fue reagendada
</h2>

<p>
    Hola {{first_name}},
</p>

<p>
    Tu cita ha sido actualizada correctamente.
</p>

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    style="
        margin:24px 0;
        background:#f8f9fa;
        border-radius:10px;
        padding:20px;
    "
>
<tr>
<td>

<p>
<strong>Servicio:</strong> {{service_name}}
</p>

<p>
<strong>Nueva fecha:</strong> {{date}}
</p>

<p>
<strong>Nueva hora:</strong> {{time}}
</p>

<p>
<strong>Referencia:</strong> {{reference_code}}
</p>

{{#old_date}}
<p>
<strong>Fecha anterior:</strong> {{old_date}}
</p>
{{/old_date}}

</td>
</tr>
</table>

{{#manage_url}}

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center">

<a href="{{manage_url}}"
   style="
       display:inline-block;
       padding:14px 26px;
       background:#10b981;
       color:#ffffff;
       text-decoration:none;
       border-radius:8px;
       font-size:14px;
       font-weight:600;
   ">
    Gestionar mi cita
</a>

<p
    style="
        font-size:12px;
        color:#888;
        margin-top:12px;
    "
>
    Puedes reagendar o cancelar nuevamente si lo necesitas.
</p>

</td>
</tr>
</table>

{{/manage_url}}

<p>
    Conserva este correo para futuras consultas relacionadas con tu cita.
</p>
',

                'body_text' => '
Tu cita fue reagendada

Hola {{first_name}}

Servicio: {{service_name}}
Nueva fecha: {{date}}
Nueva hora: {{time}}
Referencia: {{reference_code}}

Fecha anterior: {{old_date}}

Gestionar cita:
{{manage_url}}
',

                'is_active' => true,
            ]
        ); // Fin template

        /*
        |--------------------------------------------------------------------------
        | Cita - Reagenda Staff
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'appointment_client_rescheduled',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'business',

                'name' => 'Cliente reagendó cita',

                'subject' => 'Un cliente ha reagendado su cita',

                'body' => '
<h2>📅 Cita reagendada por el cliente</h2>

<p>
<strong>Cliente:</strong><br>
{{first_name}} {{last_name}}
</p>

<p>
<strong>Email:</strong><br>
{{email}}
</p>

<hr>

<p>
<strong>Servicio:</strong><br>
{{service_name}}
</p>

<p>
<strong>Fecha anterior:</strong><br>
{{old_date}}
</p>

<p>
<strong>Nueva fecha:</strong><br>
{{new_date}}
</p>

{{#note}}
<hr>

<p><strong>Nota del cliente:</strong></p>

<p>
{{note}}
</p>
{{/note}}

<hr>

<p>
<strong>Referencia:</strong><br>
{{reference_code}}
</p>

{{#confirm_url}}

<p style="margin-top:30px;">
<a href="{{confirm_url}}">
Confirmar nuevo horario
</a>
</p>

{{/confirm_url}}

{{#cancel_url}}

<p>
<a href="{{cancel_url}}">
Cancelar cita
</a>
</p>

{{/cancel_url}}
',

                'body_text' => '
Cliente reagendó su cita

Cliente:
{{first_name}} {{last_name}}

Email:
{{email}}

Servicio:
{{service_name}}

Fecha anterior:
{{old_date}}

Nueva fecha:
{{new_date}}

{{#note}}
Nota:
{{note}}
{{/note}}

Referencia:
{{reference_code}}

{{#confirm_url}}
Confirmar:
{{confirm_url}}
{{/confirm_url}}

{{#cancel_url}}
Cancelar:
{{cancel_url}}
{{/cancel_url}}
',

                'is_active' => true,
            ]
        );
    }
}
