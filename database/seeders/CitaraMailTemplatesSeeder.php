<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class CitaraMailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // CITARA - Templates


        /*
        |--------------------------------------------------------------------------
        | Bienvenida / Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'auth_welcome_user',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'CITARA Welcome Email',
                'subject' => 'Bienvenido a CITARA',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Bienvenido a CITARA
</p>

<p>
    Estás a unos pasos de empezar a recibir citas en tu negocio.
</p>

<p>
    Para activar tu cuenta, confirma tu correo:
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{verification_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Activar mi cuenta
    </a>
</td>
</tr>
</table>

<br>

<p>
    Este paso solo toma unos segundos.
</p>
        ',
                'body_text' => '
Hola {{name}}

Bienvenido a CITARA.
Activa tu cuenta aquí: {{verification_url}}
        ',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Notificación Registro / Interno
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'auth_user_registered_internal',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'New User Internal',
                'subject' => 'Nuevo usuario registrado',
                'body' => '
<h2>Nuevo usuario en CITARA</h2>

<p><strong>Nombre:</strong> {{name}}</p>
<p><strong>Email:</strong> {{email}}</p>
<p><strong>Fecha:</strong> {{date}}</p>
<p><strong>Hora:</strong> {{time}}</p>

<br>

<p>Revisar desde el panel si es necesario.</p>
        ',
                'body_text' => '
Nuevo usuario registrado

Nombre: {{name}}
Email: {{email}}
Fecha: {{date}}
Hora: {{time}}
        ',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Email confirmado / Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'auth_email_verified',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'Email Verified',
                'subject' => 'Tu cuenta ya está activa',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Tu correo ha sido confirmado correctamente.
</p>

<p>
    Ya puedes continuar configurando tu negocio y empezar a recibir citas.
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{onboarding_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Continuar configuración
    </a>
</td>
</tr>
</table>
        ',
                'body_text' => '
Hola {{name}}

Tu correo ha sido confirmado.

Continúa aquí:
{{onboarding_url}}
        ',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Verificación / Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'auth_verify_email',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'Verify Email',
                'subject' => 'Confirma tu correo',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Para desbloquear todas las funciones de CITARA necesitas confirmar tu correo.
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{verification_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Verificar correo
    </a>
</td>
</tr>
</table>

<p>
    Este paso solo toma unos segundos.
</p>
        ',
                'body_text' => '
Hola {{name}}

Verifica tu correo aquí:
{{verification_url}}
        ',
                'is_active' => true,
            ]
        );



        /*
        |--------------------------------------------------------------------------
        | Restablecer Contraseña / Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'auth_password_reset',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'Password Reset',
                'subject' => 'Restablece tu contraseña',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Recibimos una solicitud para restablecer tu contraseña.
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{reset_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Restablecer contraseña
    </a>
</td>
</tr>
</table>

<p>
    Si no solicitaste esto, puedes ignorar este mensaje.
</p>
        ',
                'body_text' => '
Hola {{name}}

Restablece tu contraseña aquí:
{{reset_url}}
        ',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Upgrade Plan / Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'billing_plan_upgraded',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'Plan Upgraded',
                'subject' => 'Tu plan PRO ya está activo',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Tu plan PRO ya está activo 🎉
</p>

<p>
    Ahora tienes acceso a más citas, mejor control de agenda y herramientas avanzadas.
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{dashboard_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Ir a mi panel
    </a>
</td>
</tr>
</table>

<p>
    Aprovecha al máximo tu cuenta y sigue creciendo 🚀
</p>
        ',
                'body_text' => '
Hola {{name}}

Tu plan PRO ya está activo.

Accede aquí:
{{dashboard_url}}
        ',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Límite de Plan  / Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'billing_limit_reached',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'Limit Reached',
                'subject' => 'Has alcanzado el límite de tu plan',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Has alcanzado el límite de citas de tu plan actual.
</p>

<p>
    Para seguir recibiendo clientes, puedes actualizar tu plan.
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{upgrade_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Actualizar plan
    </a>
</td>
</tr>
</table>

<p>
    Da el siguiente paso cuando estés listo 🚀
</p>
        ',
                'body_text' => '
Hola {{name}}

Alcanzaste el límite de tu plan.

Actualiza aquí:
{{upgrade_url}}
        ',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Cita creada  / Cliente rombi -> se tiene que mejorar
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'booking_created_user',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'Booking Created User',
                'subject' => 'Tu cita ha sido confirmada',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Tu cita ha sido confirmada correctamente ✅
</p>

<p>
    <strong>Servicio:</strong> {{service_name}}<br>
    <strong>Fecha:</strong> {{date}}<br>
    <strong>Hora:</strong> {{time}}
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{manage_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Gestionar mi cita
    </a>
</td>
</tr>
</table>

<p>
    Puedes reprogramar o cancelar desde el enlace anterior.
</p>
        ',
                'body_text' => '
Hola {{name}}

Tu cita está confirmada.

Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}

Gestiona aquí:
{{manage_url}}
        ',
                'is_active' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Cita creada negocio  / Cliente
        |--------------------------------------------------------------------------
        */
        NotificationTemplate::updateOrCreate(
            [
                'organization_id' => null,
                'type' => 'booking_created_owner',
                'channel' => 'email',
            ],
            [
                'layout_type' => 'citara',
                'name' => 'Booking Created Owner',
                'subject' => 'Nueva cita agendada',
                'body' => '
<h2>Nueva cita agendada</h2>

<p>
    Se ha registrado una nueva cita en tu agenda 📅
</p>

<p>
    <strong>Cliente:</strong> {{client_name}}<br>
    <strong>Email:</strong> {{client_email}}<br>
    <strong>Servicio:</strong> {{service_name}}<br>
    <strong>Fecha:</strong> {{date}}<br>
    <strong>Hora:</strong> {{time}}
</p>

<br>

<table cellpadding="0" cellspacing="0">
<tr>
<td align="center" bgcolor="#10b981" style="border-radius:6px;">
    <a href="{{dashboard_url}}" target="_blank"
        style="display:inline-block;padding:12px 20px;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold;">
        Ver en mi panel
    </a>
</td>
</tr>
</table>

<p>
    Revisa los detalles desde tu panel.
</p>
        ',
                'body_text' => '
Nueva cita agendada

Cliente: {{client_name}}
Email: {{client_email}}
Servicio: {{service_name}}
Fecha: {{date}}
Hora: {{time}}

Ver en panel:
{{dashboard_url}}
        ',
                'is_active' => true,
            ]
        );
    }
}
