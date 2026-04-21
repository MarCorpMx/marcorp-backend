<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class CitaraNotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | CITARA - Templates
        |--------------------------------------------------------------------------
        */

        $templates = [

            [
                'type' => 'citara_user_registered_user',
                'channel' => 'email',
                'name' => 'Bienvenida CITARA',
                'subject' => 'Tu agenda en línea ya está lista',
                'body' => '
Hola {{first_name}},

Tu cuenta en CITARA ya está activa.

Desde ahora puedes empezar a recibir citas en línea sin necesidad de llamadas o mensajes.

👉 Siguiente paso:
Configura tu primer servicio y comparte tu enlace de agenda.

{{dashboard_url}}

Entre más rápido lo hagas, más rápido empiezas a recibir clientes.

— Equipo CITARA
                ',
                'variables' => [
                    'first_name' => 'Nombre del usuario',
                    'dashboard_url' => 'Link al dashboard',
                ],
            ],

            [
                'type' => 'citara_user_registered_internal',
                'channel' => 'email',
                'name' => 'Nuevo usuario (Interno)',
                'subject' => 'Nuevo usuario registrado',
                'body' => '
Se registró un nuevo usuario en CITARA.

Nombre: {{first_name}}
Email: {{email}}
Fecha: {{date}}
Hora: {{time}}

Revisar actividad desde el panel si es necesario.
                ',
                'variables' => [
                    'first_name' => 'Nombre del usuario',
                    'email' => 'Correo del usuario',
                    'date' => 'Fecha de registro',
                    'time' => 'Hora de registro',
                ],
            ],

            [
                'type' => 'citara_verify_email',
                'channel' => 'email',
                'name' => 'Verificación de correo',
                'subject' => 'Confirma tu correo para desbloquear más citas',
                'body' => '
Hola {{first_name}},

Para aprovechar al máximo CITARA necesitas confirmar tu correo.

👉 Al verificarlo podrás:
- Recibir hasta 30 citas
- Gestionar tu agenda sin límites básicos

Actualmente estás limitado a 5 citas.

{{verification_url}}

Hazlo ahora y sigue avanzando.

— Equipo CITARA
                ',
                'variables' => [
                    'first_name' => 'Nombre del usuario',
                    'verification_url' => 'Link de verificación',
                ],
            ],

            [
                'type' => 'citara_email_not_verified_limit',
                'channel' => 'email',
                'name' => 'Límite sin verificar',
                'subject' => 'Llegaste al límite de citas sin verificar',
                'body' => '
Hola {{first_name}},

Ya alcanzaste el límite de 5 citas sin verificar tu correo.

Para seguir recibiendo nuevas citas necesitas confirmar tu email.

{{verification_url}}

Es un proceso rápido y te desbloquea hasta 30 citas.

— Equipo CITARA
                ',
                'variables' => [
                    'first_name' => 'Nombre del usuario',
                    'verification_url' => 'Link de verificación',
                ],
            ],

            [
                'type' => 'citara_free_limit_reached',
                'channel' => 'email',
                'name' => 'Límite plan free',
                'subject' => 'Has alcanzado el límite de tu plan',
                'body' => '
Hola {{first_name}},

Ya alcanzaste el límite de citas de tu plan actual.

Para seguir creciendo y recibiendo más clientes, puedes actualizar tu plan.

👉 Con el plan PRO obtienes:
- Más citas
- Mejor control de agenda
- Herramientas avanzadas

{{upgrade_url}}

Da el siguiente paso cuando estés listo.

— Equipo CITARA
                ',
                'variables' => [
                    'first_name' => 'Nombre del usuario',
                    'upgrade_url' => 'Link para actualizar plan',
                ],
            ],

            [
                'type' => 'citara_plan_upgraded',
                'channel' => 'email',
                'name' => 'Plan actualizado a PRO',
                'subject' => 'Tu plan PRO ya está activo',
                'body' => '
Hola {{first_name}},

Tu plan PRO ya está activo.

Ahora tienes acceso a más citas y mejores herramientas para gestionar tu agenda.

Aprovecha al máximo tu cuenta y sigue creciendo.

{{dashboard_url}}

— Equipo CITARA
                ',
                'variables' => [
                    'first_name' => 'Nombre del usuario',
                    'dashboard_url' => 'Link al dashboard',
                ],
            ],

        ];

        foreach ($templates as $template) {

            NotificationTemplate::updateOrCreate(
                [
                    'organization_id' => null, // GLOBAL
                    'type' => $template['type'],
                    'channel' => $template['channel'],
                ],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => trim($template['body']),
                    'body_text' => trim(strip_tags($template['body'])), // fallback simple
                    'variables' => $template['variables'],
                    'is_active' => true,
                ]
            );
        }
    }
}
