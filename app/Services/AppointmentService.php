<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BranchServiceVariant;
use App\Models\Organization;
use App\Models\Client;
use App\Models\StaffMember;
use App\Models\ClientPet;
use App\Services\AppointmentAvailabilityService;


use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;



class AppointmentService
{
    public function __construct(
        protected AppointmentAvailabilityService $availabilityService,
        protected AppointmentTokenService $tokenService,
        protected AppointmentNoteService $noteService,
        protected AppointmentNotificationService $notificationService,
        protected AppointmentTimezoneService $timezoneService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CREAR CITA
    |--------------------------------------------------------------------------
    */
    public function createAppointment(
        array $data,
        Organization $organization,
        Client $client,
        ?ClientPet $pet,
        StaffMember $staff,
        BranchServiceVariant $variant,
        array $options = []
    ): Appointment {
        return DB::transaction(function () use ($data, $organization, $client, $pet, $staff, $variant, $options) {

            // $timezone = $this->timezoneService->resolveBranchTimezone($variant->branch);
            /*$range = $this->timezoneService->buildUtcRange(
                $data['date'],
                $data['time'],
                $variant->duration_minutes,
                $timezone
            );
            $start = $range['start_utc'];
            $end = $range['end_utc'];*/

            $timezone = $data['timezone'];

            $start = Carbon::parse(
                $data['start_datetime_local'],
                $timezone
            )->utc();

            $end = $start
                ->copy()
                ->addMinutes(
                    $variant->duration_minutes
                );

            // No crear citas en fecha pasada    
            if ($start->lt(now()->utc())) {
                throw ValidationException::withMessages([
                    'start_datetime_local' => [
                        'No puedes crear citas en una fecha pasada.'
                    ]
                ]);
            }

            // Duración válida del servicio
            if (
                !$variant->duration_minutes ||
                $variant->duration_minutes <= 0
            ) {
                throw ValidationException::withMessages([
                    'service' => [
                        'El servicio no tiene una duración válida.'
                    ]
                ]);
            }

            // Modalidad online
            if (
                $data['mode'] === 'online'
                &&
                empty($data['meeting_url'])
            ) {
                throw ValidationException::withMessages([
                    'meeting_url' => [
                        'Debes indicar un enlace para la sesión.'
                    ]
                ]);
            }

            // Verificar si staff acepta online
            if (
                $data['mode'] === 'online'
                &&
                !$staff->accepts_online
            ) {
                throw ValidationException::withMessages([
                    'mode' => [
                        'Este profesional no acepta sesiones en línea.'
                    ]
                ]);
            }

            // Verificar si staff acepta presencial
            if (
                $data['mode'] === 'presential'
                &&
                !$staff->accepts_presential
            ) {
                throw ValidationException::withMessages([
                    'mode' => [
                        'Este profesional no acepta sesiones presenciales.'
                    ]
                ]);
            }

            // Verificamos la disponiblidad
            if ($options['source'] === 'admin_panel') {
                $this->availabilityService->validateOrFail(
                    $staff->id,
                    $variant->branch_id,
                    $start,
                    $end,
                    null,
                    false
                );
            } else {
                $this->availabilityService->validateOrFail(
                    $staff->id,
                    $variant->branch_id,
                    $start,
                    $end
                );
            }


            // Cliente (public - crea cliente, admin - tiene cliente)
            /*if (!empty($data['client_id'])) {
                $client = Client::findOrFail($data['client_id']);
            } else {
                $client = $this->resolveClient($data, $organization);
            }*/
            // Verificar esta parte ya que se debe de crear el usuario si no existe y viene desde el booking-public
            if ($options['source'] !== 'admin_panel') {
                $client = $this->resolveClient($data, $organization);
            }




            /*throw ValidationException::withMessages([
                'appointment' => ['estamos probando ando ']
            ]);*/

            // Crear cita
            $appointment = Appointment::create([
                'organization_id' => $organization->id,
                'branch_id' => $variant->branch_id,

                'client_id' => $client->id,
                'created_by' => $options['user_id'] ?? null,
                'pet_id' => $pet?->id,

                'staff_member_id' => $staff->id,

                'branch_service_variant_id' => $variant->id,

                'start_datetime' => $start,
                'end_datetime' => $end,

                'capacity_reserved' => $data['capacity'] ?? 1,

                //'is_exception' => $options['is_exception'] ?? false,
                'original_start_datetime' => $start,

                'status' => $options['status'] ?? 'pending',
                'source' => $options['source'] ?? 'public_web',

                'notes' => $data['notes'] ?? null,

                'mode' => $data['mode'] ?? $variant->mode,
                'meeting_url' => $data['meeting_url'] ?? null,
                'meeting_provider' => $data['meeting_provider'] ?? null,

                'timezone' => $timezone,

                'base_price' => $variant->price ?? 0,
                'final_price' => $variant->price ?? 0,
            ]);

            $noteType = 'client_created';
            $noteText = 'Cita creada desde el portal público';
            if ($options['source'] == 'admin_panel') {
                $noteType = 'staff_created';
                $noteText = 'Cita creada por el personal';
            }


            /******************** IMPORTANTE SI LAS NOTAS ESTAN VACIAS GENERA ERROR */

            // Generamos nota
            $this->noteService->create(
                appointment: $appointment,
                userId: $options['user_id'] ?? null,
                type: $noteType,
                note: $noteText
            );

            // rombi -> desactivar acciones por plan
            /*if (!$organization->hasFeature('appointment_email_actions')) {
                $confirmUrl = null;
                $cancelUrl = null;
            }*/

            // Generamos las notificaciones    

            //Log::info('OmData', ['data' => $options]);

            // Tokens (solo si aplica)
            $tokens = null;
            if ($options['with_tokens'] ?? true) {
                $tokens = $this->tokenService->generate($appointment);
            }

            if ($options['send_notifications'] ?? true) {
                $this->notificationService->sendCreated($appointment, $tokens, $options);
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

        $this->noteService->create(
            $appointment,
            $data['user_id'] ?? null,
            'confirmed',
            $source === 'email'
                ? 'Cita confirmada desde enlace de correo'
                : ($data['reason'] ?? 'Cita confirmada desde panel')
        );

        $this->notificationService->sendConfirmed($appointment);

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

        $this->tokenService->revoke($appointment);

        $note = match ($source) {
            'email' => 'Cita cancelada desde enlace de correo',
            'client' => $data['reason'] ?? 'Cita cancelada por el cliente',
            default => $data['reason'] ?? 'Cita cancelada desde panel',
        };

        $this->noteService->create(
            $appointment,
            $data['user_id'] ?? null,
            $source === 'client' ? 'client_cancellation' : 'cancellation',
            $note
        );

        $this->notificationService->sendCancelled($appointment, $note, $source);

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

        $variant = $appointment->branchServiceVariant;

        $timezone = $this->timezoneService->resolveBranchTimezone($variant->branch);

        $range = $this->timezoneService->buildUtcRange(
            $data['date'],
            $data['time'],
            $variant->duration_minutes,
            $timezone
        );

        $newStart = $range['start_utc'];
        $newEnd = $range['end_utc'];

        //$newStart = Carbon::parse($data['date'] . ' ' . $data['time']);
        //$newEnd = $newStart->copy()->addMinutes($variant->duration_minutes);

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
        $noteText = $this->noteService->buildRescheduleNote($data, $oldDate, $newStart);

        $this->noteService->create(
            $appointment,
            $data['user_id'] ?? null,
            $source === 'client' ? 'client_reschedule' : 'reschedule',
            $noteText
        );

        // Tokens (revocar + regenerar)
        $tokens = $this->tokenService->generate($appointment);

        // Notificaciones
        $this->notificationService->sendRescheduled($appointment, $noteText, $oldDate, $tokens, $source);

        return $appointment;
    }
}
