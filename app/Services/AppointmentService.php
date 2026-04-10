<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ServiceVariant;
use App\Models\Organization;
use App\Models\Client;
use App\Services\NotificationService;
use App\Models\AppointmentNote;
use App\Models\AppointmentActionToken;
use App\Services\AppointmentAvailabilityService;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;


class AppointmentService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected AppointmentAvailabilityService $availabilityService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CREAR CITA
    |--------------------------------------------------------------------------
    */
    public function createAppointment(array $data, Organization $organization, array $options = []): Appointment
    {
        return DB::transaction(function () use ($data, $organization, $options) {

            //$variant = ServiceVariant::findOrFail($data['service_variant_id']);
            $variant = ServiceVariant::where('id', $data['service_variant_id'])
                ->whereHas('service', function ($q) use ($organization) {
                    $q->where('organization_id', $organization->id);
                })
                ->firstOrFail();

            $start = Carbon::parse($data['date'] . ' ' . $data['time']);
            $end = $start->copy()->addMinutes($variant->duration_minutes);

            $conflict = Appointment::where('staff_member_id', $data['staff_member_id'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_datetime', [$start, $end])
                        ->orWhereBetween('end_datetime', [$start, $end])
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('start_datetime', '<', $start)
                                ->where('end_datetime', '>', $end);
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new \Exception('Slot ocupado');
            }


            // Cliente (public - crea cliente, admin - tiene cliente)
            if (!empty($data['client_id'])) {
                $client = Client::findOrFail($data['client_id']);
            } else {
                $client = $this->resolveClient($data, $organization);
            }

            // Conflicto (centralizado)
            $staffId = $this->assertNoConflict(
                $data['staff_member_id'] ?? null,
                $start,
                $end,
                $variant,
                $options
            );


            // Crear cita
            $appointment = Appointment::create([
                'organization_id' => $organization->id,
                'client_id' => $client->id,
                //'staff_member_id' => $data['staff_member_id'],
                'staff_member_id' => $staffId,
                'service_variant_id' => $variant->id,
                'start_datetime' => $start,
                'end_datetime' => $end,
                'capacity_reserved' => $data['capacity'] ?? 1,
                'status' => $options['status'] ?? 'pending',
                'source' => $options['source'] ?? 'public_web',
                'notes' => $data['notes'] ?? null,
                'mode' => $data['mode'] ?? 'presential',

                'base_price' => $variant->price,
                'final_price' => $variant->price,
            ]);

            $noteType = 'client_created';
            if ($options['source'] == 'admin_panel') {
                $noteType = 'staff_created';
            }


            // Generamos nota
            $this->createNote(
                appointment: $appointment,
                userId: $options['user_id'] ?? null,
                type: $noteType,
                note: $options['note'] ?? null
            );

            // rombi -> desactivar acciones por plan
            /*if (!$organization->hasFeature('appointment_email_actions')) {
                $confirmUrl = null;
                $cancelUrl = null;
            }*/

            // Tokens (solo si aplica)
            $tokens = null;
            if ($options['with_tokens'] ?? true) {
                $tokens = $this->generateTokens($appointment);
            }

            if ($options['send_notifications'] ?? true) {
                $this->notifyCreated($appointment, $tokens, $options);
            }

            return $appointment;
        });
    }

    private function resolveClient(array $data, Organization $organization): Client
    {
        $client = Client::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'email' => $data['email']
            ],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null
            ]
        );

        if (!empty($data['phone'])) {
            $client->update(['phone' => $data['phone']]);
        }

        return $client;
    }

    // Conflictos
    private function assertNoConflict(
        ?int $staffId,
        $start,
        $end,
        ServiceVariant $variant,
        array $options = []
    ): int {

        if (!$staffId) {
            // booking público → buscar staff disponible
            $availableStaff = $this->availabilityService->getAvailableStaff(
                $variant->id,
                $start,
                $end
            );

            if ($availableStaff->isEmpty()) {
                abort(422, 'No hay disponibilidad');
            }

            return $availableStaff->first()->id;
        }

        // admin → validar disponibilidad
        $isAvailable = $this->availabilityService->isStaffAvailable(
            $staffId,
            $start,
            $end
        );

        if (!$isAvailable && ($options['source'] ?? null) !== 'admin_panel') {
            abort(422, 'Horario no disponible');
        }

        return $staffId;
    }

    /*private function assertNoConflict($staffId, $start, $end): void
    {
        $conflict = Appointment::where('staff_member_id', $staffId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();

        if ($conflict) {
            abort(409, 'Este horario ya fue reservado');
        }
    }*/

    /*private function assertWithinAvailability($staffId, $start, $end): void
    {
        $staff = \App\Models\StaffMember::with([
            'schedules',
            'nonWorkingDays',
            'agendaSetting'
        ])->findOrFail($staffId);

        $date = $start->copy();
        $dayOfWeek = $date->dayOfWeek;

        // Día bloqueado
        $blocked = $staff->nonWorkingDays()
            ->whereDate('date', $date)
            ->exists();

        if ($blocked) {
            abort(422, 'Este día no está disponible');
        }

        // Horario
        $schedule = $staff->schedules
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) {
            abort(422, 'No hay horario disponible este día');
        }

        $scheduleStart = Carbon::parse($schedule->start_time)->setDateFrom($date);
        $scheduleEnd   = Carbon::parse($schedule->end_time)->setDateFrom($date);

        if ($start < $scheduleStart || $end > $scheduleEnd) {
            abort(422, 'Horario fuera de disponibilidad');
        }
    }*/



    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR CITA
    |--------------------------------------------------------------------------
    */
    public function confirm(
        Appointment $appointment,
        array $data = [],
        string $source = 'admin'
    ): Appointment {
        $appointment->update(['status' => 'confirmed']);

        $this->createNote(
            $appointment,
            $data['user_id'] ?? null,
            'confirmed',
            $source === 'email'
                ? 'Cita confirmada desde enlace de correo'
                : ($data['reason'] ?? 'Cita confirmada desde panel')
        );

        $this->notifyConfirmed($appointment);

        return $appointment;
    }

    /*
    |--------------------------------------------------------------------------
    | CANCELAR CITA
    |--------------------------------------------------------------------------
    */
    public function cancel(
        Appointment $appointment,
        array $data = [],
        string $source = 'admin'
    ): Appointment {
        // $sourse => admin | client | email

        $appointment->update(['status' => 'cancelled']);

        $this->revokeTokens($appointment);

        $note = match ($source) {
            'email' => 'Cita cancelada desde enlace de correo',
            'client' => $data['reason'] ?? 'Cita cancelada por el cliente',
            default => $data['reason'] ?? 'Cita cancelada desde panel',
        };

        $this->createNote(
            $appointment,
            $data['user_id'] ?? null,
            $source === 'client' ? 'client_cancellation' : 'cancellation',
            $note
        );

        $this->notifyCancelled($appointment, $note, $source);

        return $appointment;
    }

    /*
    |--------------------------------------------------------------------------
    | REAGENDAR CITA
    |--------------------------------------------------------------------------
    */
    public function reschedule(
        Appointment $appointment,
        array $data,
        string $source = 'client'
    ): Appointment {

        $variant = $appointment->serviceVariant;

        $newStart = Carbon::parse($data['date'] . ' ' . $data['time']);
        $newEnd = $newStart->copy()->addMinutes($variant->duration_minutes);

        // No pasado
        if ($newStart->lessThan(now())) {
            abort(422, 'No se puede reagendar a una fecha pasada');
        }

        // Conflictos (excluyendo la misma cita)
        $conflict = Appointment::where('staff_member_id', $appointment->staff_member_id)
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_datetime', '<', $newEnd)
            ->where('end_datetime', '>', $newStart)
            ->exists();

        if ($conflict) {
            abort(409, 'Este horario ya fue reservado');
        }

        // Guardar fecha anterior
        $oldDate = $appointment->start_datetime->copy();

        // Update
        $appointment->update([
            'start_datetime' => $newStart,
            'end_datetime' => $newEnd,
            'status' => 'rescheduled'
        ]);

        // Nota
        $noteText = $this->buildRescheduleNote($data, $oldDate, $newStart);

        $this->createNote(
            $appointment,
            $data['user_id'] ?? null,
            $source === 'client' ? 'client_reschedule' : 'reschedule',
            $noteText
        );

        // Tokens (revocar + regenerar)
        $tokens = $this->generateTokens($appointment);

        // Notificaciones
        $this->notifyRescheduled($appointment, $noteText, $oldDate, $tokens, $source);

        return $appointment;
    }

    /*
    |--------------------------------------------------------------------------
    | TOKENS
    |--------------------------------------------------------------------------
    */
    private function generateTokens(Appointment $appointment)
    {
        AppointmentActionToken::where('appointment_id', $appointment->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $tokens = [];

        foreach (['confirm', 'cancel'] as $action) {
            $tokens[$action] = AppointmentActionToken::create([
                'appointment_id' => $appointment->id,
                'token' => Str::uuid(),
                'action' => $action,
                'expires_at' => now()->addHours(24),
            ]);
        }

        return $tokens;
    }

    // Solo para cuando la solicitud viene desde correos
    public function handleActionToken(string $token, string $ip, ?string $userAgent = null): array
    {
        return DB::transaction(function () use ($token, $ip, $userAgent) {

            $actionToken = AppointmentActionToken::with([
                'appointment.serviceVariant.service',
                'appointment.client',
                'appointment.organization'
            ])
                ->where('token', $token)
                ->lockForUpdate()
                ->first();

            if (!$actionToken || !$actionToken->appointment) {
                return ['status' => 'invalid_token', 'appointment' => null];
            }

            $appointment = $actionToken->appointment;

            if ($actionToken->expires_at && now()->greaterThan($actionToken->expires_at)) {
                return ['status' => 'expired', 'appointment' => $appointment];
            }

            if ($actionToken->revoked_at) {
                return ['status' => 'expired', 'appointment' => $appointment];
            }

            if ($actionToken->used_at) {
                return ['status' => 'already_used', 'appointment' => $appointment];
            }

            if (in_array($appointment->status, ['confirmed', 'cancelled', 'completed', 'no_show'])) {
                return ['status' => 'already_' . $appointment->status, 'appointment' => $appointment];
            }

            // Acción
            if ($actionToken->action === 'confirm') {
                $this->confirm($appointment, [], 'email');
                $status = 'confirmed';
            } elseif ($actionToken->action === 'cancel') {
                $this->cancel($appointment, [], 'email');
                $status = 'cancelled';
            }

            // Auditoría
            $actionToken->update([
                'used_at' => now(),
                'used_ip' => $ip,
                'used_user_agent' => $userAgent,
            ]);

            return [
                'status' => $status,
                'appointment' => $appointment->fresh([
                    'serviceVariant.service',
                    'client',
                    'organization'
                ])
            ];
        });
    }

    protected function revokeTokens(Appointment $appointment, ?int $exceptTokenId = null): void
    {
        AppointmentActionToken::where('appointment_id', $appointment->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->when($exceptTokenId, fn($q) => $q->where('id', '!=', $exceptTokenId))
            ->update(['revoked_at' => now()]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTAS - CONTROL DE HISTORIAL DE CITA
    |--------------------------------------------------------------------------
    */
    public function createNote(
        Appointment $appointment,
        ?int $userId,
        string $type,
        ?string $note = null
    ): void {
        AppointmentNote::create([
            'appointment_id' => $appointment->id,
            'user_id' => $userId,
            'type' => $type,
            'note' => $note
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTIFICACIONES - ENVIO DE CORREOS
    |--------------------------------------------------------------------------
    */
    protected function notifyCreated(
        Appointment $appointment,
        ?array $tokens = null,
        array $options = []
    ): void {
        $appointment->load(['client', 'serviceVariant.service', 'organization']);

        $client = $appointment->client;
        $organization = $appointment->organization;
        $variant = $appointment->serviceVariant;

        $start = \Carbon\Carbon::parse($appointment->start_datetime);


        // Vat¿riables para botones
        $pro_tip = null;
        $confirmUrl = null;
        $cancelUrl = null;

        // URLs públicas - para notificación interna de la organización
        if ($tokens) {
            $baseUrl = config('services.booking.front_url') . "/{$organization->slug}/result";

            $confirmUrl = $baseUrl . "?token=" . $tokens['confirm']->token;
            $cancelUrl  = $baseUrl . "?token=" . $tokens['cancel']->token;
        }

        // URL pública para gestión de cita del cliente 
        $manageBaseUrl = config('services.booking.front_url') . "/{$organization->slug}/manage";
        $manageUrl = $manageBaseUrl . "?ref={$appointment->reference_code}";

        /*Log::info('Confirm URL: ' . $confirmUrl);
        Log::info('Cancel URL: ' . $cancelUrl);
        Log::info('Manage URL: ' . $manageUrl);*/

        $modeLabels = [
            'online' => 'En línea',
            'presential' => 'Presencial',
            'hybrid' => 'Híbrido'
        ];
        $mode = $modeLabels[$appointment->mode] ?? $appointment->mode;

        /*
        |--------------------------------------------------------------------------
        | EMAIL CLIENTE
        |--------------------------------------------------------------------------
        */
        if ($options['notify_client'] ?? true) {
            $type = $appointment->source === 'admin_panel'
                ? 'appointment_created_by_staff'
                : 'appointment_request_received';

            try {
                $this->notificationService->trigger(
                    type: $type,
                    data: [
                        'first_name' => $client->first_name,
                        'organization_name' => $organization->name,
                        'service_name' => $variant->service->name . ' - ' . $variant->name,
                        'date' => $start->format('d/m/Y'),
                        'time' => $start->format('H:i'),
                        'reference_code' => $appointment->reference_code,
                        'manage_url' => $manageUrl,
                    ],
                    organization: $organization,
                    recipient: $client->email,
                    recipientName: $client->first_name,
                    notifiable: $appointment,
                    subsystemCode: 'citas'
                );
            } catch (\Exception $e) {
                Log::error("Error sending booking email to client: " . $e->getMessage());
            }
        }

        /*
        |--------------------------------------------------------------------------
        | NOTIFICACION INTERNA
        |--------------------------------------------------------------------------
        */
        if ($options['notify_internal'] ?? true) {
            try {
                $this->notificationService->trigger(
                    type: 'appointment_internal_notification',
                    data: [
                        'last_name' => $client->last_name,
                        'email' => $client->email,
                        'phone' => $client->phone['e164Number'] ?? null,
                        'service_name' => $variant->service->name . ' - ' . $variant->name,
                        'date' => $start->format('d/m/Y'),
                        'time' => $start->format('H:i'),
                        'notes' => $appointment->notes ?? 'Sin notas adicionales',
                        'organization_name' => $organization->name,
                        'mode' => $mode,
                        'reference_code' => $appointment->reference_code,
                        'confirm_url' => $confirmUrl,
                        'cancel_url' => $cancelUrl,
                        'pro_tip' => $pro_tip,
                    ],
                    organization: $organization,
                    recipient: null,
                    recipientName: null,
                    notifiable: $appointment,
                    subsystemCode: 'citas',
                    applyNotificationRecipients: true
                );
            } catch (\Exception $e) {
                Log::error("Error sending booking notification: " . $e->getMessage());
            }
        }
    }

    protected function notifyConfirmed(Appointment $appointment): void
    {
        $appointment->load(['client', 'serviceVariant.service', 'organization']);

        $client = $appointment->client;
        $organization = $appointment->organization;
        $variant = $appointment->serviceVariant;
        $start = Carbon::parse($appointment->start_datetime);

        // URL pública para gestión de cita del cliente 
        $manageBaseUrl = config('services.booking.front_url') . "/{$organization->slug}/manage";
        $manageUrl = $manageBaseUrl . "?ref={$appointment->reference_code}";

        try {
            $this->notificationService->trigger(
                'appointment_confirmed',
                [
                    'first_name' => $client->first_name,
                    'organization_name' => $organization->name,
                    'service_name' => $variant->service->name . ' - ' . $variant->name,
                    'date' => $start->format('d/m/Y'),
                    'time' => $start->format('H:i'),
                    'reference_code' => $appointment->reference_code,
                    'manage_url' => $manageUrl,
                ],


                organization: $organization,
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

    protected function notifyCancelled(
        Appointment $appointment,
        ?string $note = null,
        string $source = 'admin'
    ): void {

        $appointment->load(['client', 'serviceVariant.service', 'organization']);

        $client = $appointment->client;
        $organization = $appointment->organization;
        $variant = $appointment->serviceVariant;
        $start = Carbon::parse($appointment->start_datetime);


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
                        'service_name' => $variant->service->name . ' - ' . $variant->name,
                        'date' => $start->format('d/m/Y'),
                        'time' => $start->format('H:i'),
                        //'date' => $appointment->start_datetime->format('d/m/Y'),
                        //'time' => $appointment->start_datetime->format('H:i'),
                        'reference_code' => $appointment->reference_code,
                        'note' => $note,
                    ],
                    organization: $organization,
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
                    'service_name' => $variant->service->name . ' - ' . $variant->name,
                    'date' => $start->format('d/m/Y'),
                    'time' => $start->format('H:i'),
                    'reference_code' => $appointment->reference_code,
                    //'manage_url' => $manageUrl,
                    'booking_url' => $bookingUrl,
                ],
                organization: $organization,
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

    protected function notifyRescheduled(
        Appointment $appointment,
        string $note,
        $oldDate,
        array $tokens,
        string $source
    ): void {

        $appointment->load(['client', 'serviceVariant.service', 'organization']);

        $client = $appointment->client;
        $organization = $appointment->organization;
        $variant = $appointment->serviceVariant;

        $newDate = Carbon::parse($appointment->start_datetime);

        // URLs
        $baseUrl = config('services.booking.front_url') . "/{$organization->slug}/result";

        $confirmUrl = $baseUrl . "?token=" . $tokens['confirm']->token;
        $cancelUrl  = $baseUrl . "?token=" . $tokens['cancel']->token;

        $manageBaseUrl = config('services.booking.front_url') . "/{$organization->slug}/manage";
        $manageUrl = $manageBaseUrl . "?ref={$appointment->reference_code}";

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
                        'service_name' => $variant->service->name . ' - ' . $variant->name,

                        'date' => $newDate->format('d/m/Y'),
                        'time' => $newDate->format('H:i'),

                        'old_date' => $oldDate->format('d/m/Y H:i'),
                        'new_date' => $newDate->format('d/m/Y H:i'),

                        'reference_code' => $appointment->reference_code,
                        'manage_url' => $manageUrl,
                    ],
                    organization: $organization,
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

                        'service_name' => $variant->service->name . ' - ' . $variant->name,

                        'old_date' => $oldDate->format('d/m/Y H:i'),
                        'new_date' => $newDate->format('d/m/Y H:i'),

                        'note' => $note,

                        'confirm_url' => $confirmUrl,
                        'cancel_url' => $cancelUrl,
                    ],
                    organization: $organization,
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
                    'service_name' => $variant->service->name . ' - ' . $variant->name,

                    'old_date' => $oldDate->format('d/m/Y H:i'),
                    'new_date' => $newDate->format('d/m/Y H:i'),

                    'reference_code' => $appointment->reference_code,

                    'confirm_url' => $confirmUrl, // 🔥 ahora sí necesarios
                    'cancel_url' => $cancelUrl,

                    'manage_url' => $manageUrl,
                ],
                organization: $organization,
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


    // helpers
    protected function buildRescheduleNote($data, $oldDate, $newDate): string
    {
        $parts = [];

        $parts[] = "Reagendada de {$oldDate->format('Y-m-d H:i')} a " . $newDate->format('Y-m-d H:i');

        if (!empty($data['reason'])) {
            $parts[] = "Motivo: {$data['reason']}";
        }

        if (!empty($data['note'])) {
            $parts[] = "Comentario: {$data['note']}";
        }

        return implode(' | ', $parts);
    }
}
