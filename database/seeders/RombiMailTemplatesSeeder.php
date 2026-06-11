<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class RombiMailTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        // ROMBI - Templates
        
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
                'layout_type' => 'rombi',
                'name' => 'ROMBI Welcome Email',
                'subject' => 'Bienvenid@ a ROMBI',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Bienvenid@ a ROMBI
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

Bienvenido a ROMBI.
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
                'layout_type' => 'rombi',
                'name' => 'New User Internal',
                'subject' => 'Nuevo usuario registrado',
                'body' => '
<h2>Nuevo usuario en ROMBI</h2>

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
                'layout_type' => 'rombi',
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
                'layout_type' => 'rombi',
                'name' => 'Verify Email',
                'subject' => 'Confirma tu correo',
                'body' => '
<h2>Hola {{name}}</h2>

<p>
    Para desbloquear todas las funciones de ROMBI necesitas confirmar tu correo.
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
                'layout_type' => 'rombi',
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
                'layout_type' => 'rombi',
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
                'layout_type' => 'rombi',
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

        
    }
}
