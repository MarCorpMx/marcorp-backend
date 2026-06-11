<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentNotificationService
{

    public function __construct(
        protected NotificationService $notificationService,
        protected AppointmentTimezoneService $timezoneService,
        protected LocationService $locationService,
    ) {}

    // Método para cargar relaciones
    protected function loadAppointment(
        Appointment $appointment
    ): Appointment {

        return $appointment->load([
            'client',
            'staff',
            'branch',
            'organization',
            'branchServiceVariant.branchService',
        ]);
    }

    // Obtener fecha local
    protected function getLocalStart(
        Appointment $appointment
    ): Carbon {

        $timezone =
            $appointment->branch?->timezone
            ?? config('app.timezone');

        return $this->timezoneService->utcToLocal(
            $appointment->start_datetime,
            $timezone
        );
    }

    // Obtener nombre del servicio
    protected function getServiceLabel(
        Appointment $appointment
    ): string {

        $service = $appointment->branchServiceVariant?->branchService?->name;

        $variant = $appointment->branchServiceVariant?->name;


        return trim(
            "{$service} - {$variant}",
            ' -'
        );
    }

    // URL gestión
    protected function buildManageUrl(
        Appointment $appointment
    ): string {

        return config('services.booking.front_url')
            . "/{$appointment->organization->slug}"
            . "/manage?ref={$appointment->reference_code}";
    }

    // URL token
    protected function buildTokenUrl(
        Appointment $appointment,
        string $token
    ): string {

        return config('services.booking.front_url')
            . "/{$appointment->organization->slug}"
            . "/result?token={$token}";
    }

    // Configurar "modo" de la cita
    private function getModeLabel(?string $mode): ?string
    {
        return match ($mode) {
            'online' => 'En línea',
            'presential' => 'Presencial',
            default => $mode,
        };
    }


    // Obtener datos de la cita
    private function buildAppointmentNotificationData(
        Appointment $appointment,
        array $extra = []
    ): array {
        $appointment = $this->loadAppointment($appointment);

        $start = $this->getLocalStart($appointment);

        $client = $appointment->client;

        // Nombre del cliente
        $displayName = null;

        if ($client) {
            $displayName = $client->preferred_name
                ?: trim(
                    $client->first_name .
                        ($client->last_name ? ' ' . $client->last_name : '')
                );
        }

        // Dirección
        $branchAddress = $appointment->branch
            ? $this->locationService->buildBranchAddress($appointment->branch)
            : null;

        $mapsUrl = $appointment->branch
            ? $this->locationService->buildGoogleMapsUrl($appointment->branch)
            : null;

        $directionsUrl = $appointment->branch
            ? $this->locationService->buildGoogleMapsDirectionsUrl($appointment->branch)
            : null;


        return array_merge([

            'first_name' => $appointment->client?->first_name,
            'last_name' => $appointment->client?->last_name,
            'full_name' => trim(
                $appointment->client?->first_name . ' ' . ($appointment->client?->last_name ?? '')
            ),
            'display_name' => $displayName,
            'friendly_name' => $appointment->client
                ? (
                    $appointment->client->preferred_name
                    ?: $appointment->client->first_name
                )
                : null,


            'email' => $appointment->client?->email,

            'organization_name' => $appointment->organization?->name,

            'service_name' => $this->getServiceLabel($appointment),
            'duration_label' => $appointment->branchServiceVariant
                ? $appointment->branchServiceVariant->duration_minutes . ' minutos'
                : null,

            'staff_name' => $appointment->staffMember?->display_name,

            'mode' => $this->getModeLabel($appointment->mode),

            'date' => $start->format('d/m/Y'),
            'time' => $start->format('H:i'),

            'reference_code' => $appointment->reference_code,

            'notes' => $appointment->notes,

            'meeting_url' => $appointment->mode === 'online'
                ? $appointment->meeting_url
                : null,

            'meeting_provider' => $appointment->meeting_provider,

            'branch_name' => $appointment->branch?->name,

            'branch_address' => $appointment->mode === 'presential'
                ? $branchAddress
                : null,

            'maps_url' => $appointment->mode === 'presential'
                ? $mapsUrl
                : null,

            'directions_url' => $appointment->mode === 'presential'
                ? $directionsUrl
                : null,

            'final_price_formatted' => number_format(
                $appointment->final_price,
                2
            ),
            'deposit_amount' => $appointment->deposit_amount > 0
                ? number_format($appointment->deposit_amount, 2)
                : null,


            // mascota
            'pet_name' => $appointment->pet?->name,
            'pet_species' => $appointment->pet?->species,
            'pet_breed' => $appointment->pet?->breed,

        ], $extra);
    }

    /*
    |--------------------------------------------------------------------------
    | Crear cita
    |--------------------------------------------------------------------------
    */

    public function sendCreated(
        Appointment $appointment,
        ?array $tokens = null,
        array $options = []
    ): void {

        $appointment = $this->loadAppointment($appointment);
        $branch = $appointment->branch;

        $start = $this->getLocalStart($appointment);

        $client = $appointment->client;

        $organization = $appointment->organization;

        $serviceName = $this->getServiceLabel($appointment);

        $manageUrl = $this->buildManageUrl($appointment);



        /*
        |--------------------------------------------------------------------------
        | CLIENTE
        |--------------------------------------------------------------------------
        */

        if ($options['notify_client'] ?? true) {


            $type =
                $appointment->source === 'admin_panel'
                ? 'appointment_created_by_staff'
                : 'appointment_request_received';


            $data = $this->buildAppointmentNotificationData(
                $appointment,
                [
                    'manage_url' => $manageUrl,
                    //'confirm_url' => $confirmUrl,
                    //'cancel_url' => $cancelUrl,
                ]
            );

            $this->notificationService->trigger(
                type: $type,
                data: $data,

                organization: $organization,
                branch: $branch,

                recipient: $client->email,

                recipientName: $client->first_name,

                notifiable: $appointment,

                subsystemCode: 'citas'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | INTERNO
        |--------------------------------------------------------------------------
        */

        if ($options['notify_internal'] ?? true) {

            $confirmUrl = null;
            $cancelUrl = null;

            if ($tokens) {

                $confirmUrl =
                    $this->buildTokenUrl(
                        $appointment,
                        $tokens['confirm']->token
                    );

                $cancelUrl =
                    $this->buildTokenUrl(
                        $appointment,
                        $tokens['cancel']->token
                    );
            }

            $this->notificationService->trigger(
                type: 'appointment_internal_notification',

                data: [

                    'first_name' => $client->first_name,

                    'last_name' => $client->last_name,

                    'email' => $client->email,

                    'phone' => $client->phone['e164Number'] ?? null,

                    'service_name' => $serviceName,

                    'date' => $start->format('d/m/Y'),

                    'time' => $start->format('H:i'),

                    'notes' => $appointment->notes,

                    'reference_code' => $appointment->reference_code,

                    'confirm_url' => $confirmUrl,

                    'cancel_url' => $cancelUrl,
                ],

                organization: $organization,
                branch: $branch,

                recipient: null,

                recipientName: null,

                notifiable: $appointment,

                subsystemCode: 'citas',

                applyNotificationRecipients: true
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Confirmar cita
    |--------------------------------------------------------------------------
    */

    public function sendConfirmed(
        Appointment $appointment
    ): void {
        $appointment = $this->loadAppointment($appointment);
        $branch = $appointment->branch;

        $client = $appointment->client;
        $organization = $appointment->organization;
        $serviceName = $this->getServiceLabel($appointment);
        //$start = Carbon::parse($appointment->start_datetime);
        $start = $this->getLocalStart(
            $appointment
        );
        // URL pública para gestión de cita del cliente 
        //$manageBaseUrl = config('services.booking.front_url') . "/{$organization->slug}/manage";
        //$manageUrl = $manageBaseUrl . "?ref={$appointment->reference_code}";
        $manageUrl = $this->buildManageUrl(
            $appointment
        );


        try {
            $this->notificationService->trigger(
                'appointment_confirmed',
                [
                    'first_name' => $client->first_name,
                    'organization_name' => $organization->name,
                    'service_name' => $serviceName,
                    'date' => $start->format('d/m/Y'),
                    'time' => $start->format('H:i'),
                    'reference_code' => $appointment->reference_code,
                    'manage_url' => $manageUrl,
                ],


                organization: $organization,
                branch: $branch,
                recipient: $client->email,
                recipientName: $client->first_name,
                notifiable: $appointment,
                subsystemCode: 'citas',
                applyNotificationRecipients: false
            );
        } catch (\Exception $e) {
            Log::error("Error sending confirmation email: " . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cancelar cita
    |--------------------------------------------------------------------------
    */
    public function sendCancelled(
        Appointment $appointment,
        ?string $note = null,
        string $source = 'admin'
    ): void {
        $appointment = $this->loadAppointment($appointment);
        $branch = $appointment->branch;

        $client = $appointment->client;
        $organization = $appointment->organization;
        $serviceName = $this->getServiceLabel($appointment);
        $start = $this->getLocalStart(
            $appointment
        );


        /*
        |-------------------------------------------------
        | CLIENTE CANCELA -> email solo a staff
        |-------------------------------------------------
        */
        if ($source === 'client') {
            try {
                $this->notificationService->trigger(
                    'appointment_client_cancelled',
                    [
                        'first_name' => $client->first_name,
                        'last_name' => $client->last_name,
                        'email' => $client->email,
                        'service_name' => $serviceName,
                        'date' => $start->format('d/m/Y'),
                        'time' => $start->format('H:i'),
                        //'date' => $appointment->start_datetime->format('d/m/Y'),
                        //'time' => $appointment->start_datetime->format('H:i'),
                        'reference_code' => $appointment->reference_code,
                        'note' => $note,
                    ],
                    organization: $organization,
                    branch: $branch,
                    recipient: null,
                    recipientName: null,
                    notifiable: $appointment,
                    subsystemCode: 'citas',
                    applyNotificationRecipients: true
                );
            } catch (\Exception $e) {
                Log::error("Error staff notification: " . $e->getMessage());
            }

            return;
        }

        /*
        |-------------------------------------------------
        | STAFF CANCELA (admin o email) -> email solo a cliente
        |-------------------------------------------------
        */
        // Ruta del booking-public
        $bookingUrl = config('services.booking.front_url') . "/{$organization->slug}";

        try {
            $this->notificationService->trigger(
                'appointment_cancelled',
                [
                    'first_name' => $client->first_name,
                    'organization_name' => $organization->name,
                    'service_name' => $serviceName,
                    'date' => $start->format('d/m/Y'),
                    'time' => $start->format('H:i'),
                    'reference_code' => $appointment->reference_code,
                    //'manage_url' => $manageUrl,
                    'booking_url' => $bookingUrl,
                ],
                organization: $organization,
                branch: $branch,
                recipient: $client->email,
                recipientName: $client->first_name,
                notifiable: $appointment,
                subsystemCode: 'citas',
                applyNotificationRecipients: false
            );
        } catch (\Exception $e) {
            Log::error("Error sending confirmation email: " . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reagendar cita
    |--------------------------------------------------------------------------
    */
    public function sendRescheduled(
        Appointment $appointment,
        string $note,
        Carbon $oldDateLocal,
        array $tokens,
        string $source
    ): void {
        $appointment = $this->loadAppointment($appointment);
        $branch = $appointment->branch;

        $client = $appointment->client;
        $organization = $appointment->organization;
        $serviceName = $this->getServiceLabel($appointment);

        $newDate = $this->getLocalStart(
            $appointment
        );

        // URLs
        //$baseUrl = config('services.booking.front_url') . "/{$organization->slug}/result";
        //$confirmUrl = $baseUrl . "?token=" . $tokens['confirm']->token;
        //$cancelUrl  = $baseUrl . "?token=" . $tokens['cancel']->token;
        $confirmUrl = isset($tokens['confirm'])
            ? $this->buildTokenUrl(
                $appointment,
                $tokens['confirm']->token
            )
            : null;

        $cancelUrl = isset($tokens['cancel'])
            ? $this->buildTokenUrl(
                $appointment,
                $tokens['cancel']->token
            )
            : null;

        //$manageBaseUrl = config('services.booking.front_url') . "/{$organization->slug}/manage";
        //$manageUrl = $manageBaseUrl . "?ref={$appointment->reference_code}";
        $manageUrl = $this->buildManageUrl(
            $appointment
        );

        /*
        |-------------------------------------------------
        | CLIENTE REAGENDA -> notificación a staff y a cliente
        |-------------------------------------------------
        */
        if ($source === 'client') {
            // Notificación a CLIENTE
            try {
                $this->notificationService->trigger(
                    'appointment_rescheduled',
                    [
                        'first_name' => $client->first_name,
                        'organization_name' => $organization->name,
                        'service_name' => $serviceName,

                        'date' => $newDate->format('d/m/Y'),
                        'time' => $newDate->format('H:i'),

                        'old_date' => $oldDateLocal->format('d/m/Y H:i'),
                        'new_date' => $newDate->format('d/m/Y H:i'),

                        'reference_code' => $appointment->reference_code,
                        'manage_url' => $manageUrl,
                    ],
                    organization: $organization,
                    branch: $branch,
                    recipient: $client->email,
                    recipientName: $client->first_name,
                    notifiable: $appointment,
                    subsystemCode: 'citas',
                    applyNotificationRecipients: false
                );
            } catch (\Exception $e) {
                Log::error("Error sending reschedule email to client: " . $e->getMessage());
            }

            // Notificación INTERNA
            try {
                $this->notificationService->trigger(
                    'appointment_client_rescheduled',
                    [
                        'first_name' => $client->first_name,
                        'last_name' => $client->last_name,
                        'email' => $client->email,

                        'service_name' => $serviceName,

                        'old_date' => $oldDateLocal->format('d/m/Y H:i'),
                        'new_date' => $newDate->format('d/m/Y H:i'),

                        'note' => $note,

                        'confirm_url' => $confirmUrl,
                        'cancel_url' => $cancelUrl,
                    ],
                    organization: $organization,
                    branch: $branch,
                    recipient: null,
                    recipientName: null,
                    notifiable: $appointment,
                    subsystemCode: 'citas',
                    applyNotificationRecipients: true
                );
            } catch (\Exception $e) {
                Log::error("Error sending reschedule email to staff: " . $e->getMessage());
            }

            return;
        }

        /*
        |-------------------------------------------------
        | STAFF REAGENDA -> notificación solo a cliente
        |-------------------------------------------------
        */
        try {
            $this->notificationService->trigger(
                'appointment_reschedule_proposed', // 🔥 nuevo template
                [
                    'first_name' => $client->first_name,
                    'organization_name' => $organization->name,
                    'service_name' => $serviceName,

                    'old_date' => $oldDateLocal->format('d/m/Y H:i'),
                    'new_date' => $newDate->format('d/m/Y H:i'),

                    'reference_code' => $appointment->reference_code,

                    'confirm_url' => $confirmUrl, // 🔥 ahora sí necesarios
                    'cancel_url' => $cancelUrl,

                    'manage_url' => $manageUrl,
                ],
                organization: $organization,
                branch: $branch,
                recipient: $client->email,
                recipientName: $client->first_name,
                notifiable: $appointment,
                subsystemCode: 'citas',
                applyNotificationRecipients: false
            );
        } catch (\Exception $e) {
            Log::error("Error sending reschedule email (staff->client): " . $e->getMessage());
        }
    }
}
