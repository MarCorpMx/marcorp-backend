<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\AppointmentActionToken;
use Illuminate\Support\Str;

class PublicAppointmentManageController extends Controller
{

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function show($reference_code): JsonResponse
    {
        $appointment = Appointment::with([
            'serviceVariant.service',
            'client',
            'staff'
        ])
            ->where('reference_code', $reference_code)
            ->first();

        if (!$appointment) {
            return response()->json([
                'message' => 'Cita no encontrada'
            ], 404);
        }

        return response()->json([
            'id' => $appointment->id,
            'reference_code' => $appointment->reference_code,
            'status' => $appointment->status,

            /*
            |--------------------------------------------------------------------------
            | SERVICE
            |--------------------------------------------------------------------------
            */
            'service' => [
                'name' => optional($appointment->serviceVariant->service)->name,
            ],

            /*
            |--------------------------------------------------------------------------
            | VARIANT
            |--------------------------------------------------------------------------
            */
            'variant' => [
                'id' => $appointment->serviceVariant->id,
                'name' => $appointment->serviceVariant->name,
                'duration' => $appointment->serviceVariant->duration_minutes,
                'price' => $appointment->serviceVariant->price,
            ],

            /*
            |--------------------------------------------------------------------------
            | MODE (CLAVE)
            |--------------------------------------------------------------------------
            */
            'mode' => $appointment->mode,

            /*
            |--------------------------------------------------------------------------
            | DATETIME
            |--------------------------------------------------------------------------
            */
            'date' => $appointment->start_datetime->format('Y-m-d'),
            'time' => $appointment->start_datetime->format('H:i'),
            'end_time' => $appointment->end_datetime->format('H:i'),

            /*
            |--------------------------------------------------------------------------
            | CLIENT
            |--------------------------------------------------------------------------
            */
            'client' => [
                'id' => optional($appointment->client)->id,
                'first_name' => optional($appointment->client)->first_name,
                'last_name' => optional($appointment->client)->last_name,
                'email' => optional($appointment->client)->email,
                'phone' => optional($appointment->client)->phone,
            ],

            /*
            |--------------------------------------------------------------------------
            | STAFF (opcional pero PRO)
            |--------------------------------------------------------------------------
            */
            'staff' => [
                'id' => optional($appointment->staff)->id,
                'name' => optional($appointment->staff)->name,
            ],

            /*
            |--------------------------------------------------------------------------
            | EXTRA
            |--------------------------------------------------------------------------
            */
            'notes' => $appointment->notes,
        ]);
    }


    public function cancel(Request $request, $reference_code)
    {
        $appointment = \App\Models\Appointment::where('reference_code', $reference_code)
            ->with(['client', 'serviceVariant.service'])
            ->first();

        if (!$appointment) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'La referencia no es válida.'
            ], 404);
        }

        // Evitar cancelar estados no válidos
        if (in_array($appointment->status, ['cancelled', 'completed', 'no_show'])) {
            return response()->json([
                'status' => 'invalid_state',
                'message' => 'Esta cita ya no puede ser cancelada.'
            ], 409);
        }

        // Validación
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // Cancelar
        $appointment->status = 'cancelled';
        $appointment->save();

        // Invalidar Tokens
        AppointmentActionToken::where('appointment_id', $appointment->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now()
            ]);

        // Construir nota
        $noteText = $this->buildClientNote($validated);

        // Guardar nota
        \App\Models\AppointmentNote::create([
            'appointment_id' => $appointment->id,
            'user_id' => null,
            'note' => $noteText,
            'type' => 'client_cancellation'
        ]);

        // EVENTOS 
        $this->notifyCancellation($appointment, $noteText);

        return response()->json([
            'status' => 'cancelled',
            'message' => 'Tu cita ha sido cancelada correctamente.'
        ]);
    }

    public function reschedule(Request $request, $reference_code)
    {
        $appointment = Appointment::with(['serviceVariant', 'staff'])
            ->where('reference_code', $reference_code)
            ->first();

        if (!$appointment) {
            return response()->json([
                'status' => 'invalid',
                'message' => 'La referencia no es válida.'
            ], 404);
        }

        // Estados no válidos
        if (in_array($appointment->status, ['cancelled', 'completed', 'no_show'])) {
            return response()->json([
                'status' => 'invalid_state',
                'message' => 'Esta cita no puede ser reagendada.'
            ], 409);
        }

        // Validación
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $variant = $appointment->serviceVariant;

        $newStart = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['time']);
        $newEnd = $newStart->copy()->addMinutes($variant->duration_minutes);

        // No permitir pasado
        if ($newStart->lessThan(now())) {
            return response()->json([
                'message' => 'No se puede reagendar a una fecha pasada'
            ], 422);
        }

        // Verificar conflictos (EXCLUYENDO la cita actual)
        $conflict = Appointment::where('staff_member_id', $appointment->staff_member_id)
            ->where('id', '!=', $appointment->id)
            ->where('start_datetime', '<', $newEnd)
            ->where('end_datetime', '>', $newStart)
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'Este horario ya fue reservado'
            ], 409);
        }

        // Guardar fecha anterior (pro level)
        $oldDate = $appointment->start_datetime->format('Y-m-d H:i');
        //$oldDate = $appointment->start_datetime->copy();

        // Actualizar cita
        $appointment->update([
            'start_datetime' => $newStart,
            'end_datetime' => $newEnd,
            'status' => 'rescheduled'
        ]);

        // Construir nota
        $noteText = $this->buildRescheduleNote($validated, $oldDate, $newStart);

        // Guardar nota interna
        \App\Models\AppointmentNote::create([
            'appointment_id' => $appointment->id,
            'user_id' => null, // viene del cliente
            'note' => $noteText,
            'type' => 'client_reschedule'
        ]);

        // Enviar correos
        $this->notifyReschedule($appointment, $noteText, $oldDate);

        return response()->json([
            'status' => 'rescheduled',
            'message' => 'Tu cita ha sido reagendada correctamente.'
        ]);
    }

    // Helpers
    private function buildClientNote($data)
    {
        $parts = [];

        if (!empty($data['reason'])) {
            $parts[] = "Motivo: {$data['reason']}";
        }

        if (!empty($data['note'])) {
            $parts[] = "Comentario: {$data['note']}";
        }

        return implode(' | ', $parts);
    }

    private function buildRescheduleNote($data, $oldDate, $newDate)
    {
        $parts = [];

        $parts[] = "Reagendada de {$oldDate} a " . $newDate->format('Y-m-d H:i');

        if (!empty($data['reason'])) {
            $parts[] = "Motivo: {$data['reason']}";
        }

        if (!empty($data['note'])) {
            $parts[] = "Comentario: {$data['note']}";
        }

        return implode(' | ', $parts);
    }

    private function notifyCancellation($appointment, $note)
    {
        $organization = $appointment->organization()->first();
        $client = $appointment->client;
        $variant = $appointment->serviceVariant;

        // Notificar (staff / admin)
        // FUTURO: WhatsApp / SMS / Push

        try {
            $this->notificationService->trigger(
                'client_cancelled_appointment',
                [
                    'first_name' => $appointment->client->first_name,
                    'last_name' => $appointment->client->last_name,
                    'email' => $appointment->client->email,
                    'service_name' => $variant->service->name . ' - ' . $variant->name,
                    'date' => $appointment->start_datetime->format('d/m/Y'),
                    'time' => $appointment->start_datetime->format('H:i'),
                    'reference_code' => $appointment->reference_code,
                    'note' => $note,
                ],
                organization: $appointment->organization,
                recipient: null,
                recipientName: null,
                notifiable: $appointment,
                subsystemCode: 'citas',
                applyNotificationRecipients: true
            );
        } catch (\Exception $e) {
            Log::error("Error sending cancellation-client email:: " . $e->getMessage());
        }
    }

    private function notifyReschedule($appointment, $note, $oldDate)
    {
        $organization = $appointment->organization()->first();
        $client = $appointment->client;
        $variant = $appointment->serviceVariant;

        /*
        |--------------------------------------------------------------------------
        | REVOCAR TOKENS ANTERIORES
        |--------------------------------------------------------------------------
        */
        AppointmentActionToken::where('appointment_id', $appointment->id)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now()
            ]);

        /*
        |--------------------------------------------------------------------------
        | CREAR NUEVOS TOKENS
        |--------------------------------------------------------------------------
        */
        $confirmToken = AppointmentActionToken::create([
            'appointment_id' => $appointment->id,
            'token' => Str::uuid()->toString(),
            'action' => 'confirm',
            'expires_at' => now()->addHours(24),
        ]);

        $cancelToken = AppointmentActionToken::create([
            'appointment_id' => $appointment->id,
            'token' => Str::uuid()->toString(),
            'action' => 'cancel',
            'expires_at' => now()->addHours(24),
        ]);

        /*
        |--------------------------------------------------------------------------
        | GENERAR URLs
        |--------------------------------------------------------------------------
        */
        $baseUrl = config('services.booking.front_url') . "/{$organization->slug}/result";

        $confirmUrl = $baseUrl . "?token={$confirmToken->token}";
        $cancelUrl  = $baseUrl . "?token={$cancelToken->token}";

        $manageBaseUrl = config('services.booking.front_url') . "/{$organization->slug}/manage";
        $manageUrl = $manageBaseUrl . "?ref={$appointment->reference_code}";

        /*
        |--------------------------------------------------------------------------
        |  FORMATEAR DATOS
        |--------------------------------------------------------------------------
        */
        //$oldDate = $appointment->getOriginal('start_datetime');
        $newDate = $appointment->start_datetime;


        // Notificación a CLIENTE
        try {
            $this->notificationService->trigger(
                type: 'appointment_rescheduled',
                data: [
                    'first_name' => $appointment->client->first_name,
                    'organization_name' => $organization->name,
                    'service_name' => $appointment->serviceVariant->service->name . ' - ' . $appointment->serviceVariant->name,

                    'date' => $newDate->format('d/m/Y'),
                    'time' => $newDate->format('H:i'),

                    'old_date' => \Carbon\Carbon::parse($oldDate)->format('d/m/Y H:i'),
                    'new_date' => $newDate->format('d/m/Y H:i'),

                    'reference_code' => $appointment->reference_code,
                    'manage_url' => $manageUrl,
                ],
                organization: $organization,
                recipient: $appointment->client->email,
                recipientName: $appointment->client->first_name,
                notifiable: $appointment,
                subsystemCode: 'citas'
            );
        } catch (\Exception $e) {
            Log::error("Error sending reschedule email to client: " . $e->getMessage());
        }

        // Notificación INTERNA
        // FUTURO: WhatsApp / SMS / Push
        try {
            $this->notificationService->trigger(
                'client_rescheduled_appointment',
                [
                    'first_name' => $client->first_name,
                    'last_name' => $client->last_name,
                    'email' => $client->email,

                    'service_name' => $variant->service->name . ' - ' . $variant->name,

                    'old_date' => \Carbon\Carbon::parse($oldDate)->format('d/m/Y H:i'),
                    'new_date' => $newDate->format('d/m/Y H:i'),

                    'note' => $note,

                    'confirm_url' => $confirmUrl,
                    'cancel_url' => $cancelUrl,
                ],
                organization: $appointment->organization,
                recipient: null,
                recipientName: null,
                notifiable: $appointment,
                subsystemCode: 'citas',
                applyNotificationRecipients: true
            );
        } catch (\Exception $e) {
            Log::error("Error sending Reschedule-client email:: " . $e->getMessage());
        }
    }
}
