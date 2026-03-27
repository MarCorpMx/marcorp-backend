<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicAppointmentManageController extends Controller
{
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
            | MODE (CLAVE 🔥)
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

        // 🚫 Estados no válidos
        if (in_array($appointment->status, ['cancelled', 'completed', 'no_show'])) {
            return response()->json([
                'status' => 'invalid_state',
                'message' => 'Esta cita no puede ser reagendada.'
            ], 409);
        }

        // ✅ Validación
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required'],
            'reason' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $variant = $appointment->serviceVariant;

        $newStart = \Carbon\Carbon::parse($validated['date'] . ' ' . $validated['time']);
        $newEnd = $newStart->copy()->addMinutes($variant->duration_minutes);

        // 🚫 No permitir pasado
        if ($newStart->lessThan(now())) {
            return response()->json([
                'message' => 'No se puede reagendar a una fecha pasada'
            ], 422);
        }

        // 🔥 Verificar conflictos (EXCLUYENDO la cita actual)
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

        // 💾 Guardar fecha anterior (pro level)
        $oldDate = $appointment->start_datetime->format('Y-m-d H:i');

        // ✏️ Actualizar cita
        $appointment->update([
            'start_datetime' => $newStart,
            'end_datetime' => $newEnd,
            'status' => 'rescheduled'
        ]);

        // 🧾 Construir nota
        $noteText = $this->buildRescheduleNote($validated, $oldDate, $newStart);

        // 🧠 Guardar nota interna
        \App\Models\AppointmentNote::create([
            'appointment_id' => $appointment->id,
            'user_id' => null, // viene del cliente
            'note' => $noteText,
            'type' => 'client_reschedule'
        ]);

        // 🔔 EVENTOS FUTUROS
        $this->notifyReschedule($appointment, $noteText);

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
        // Notificar a Michelle (staff / admin)
        // ejemplo:
        // Notification::send($appointment->staff, new AppointmentCancelledNotification(...));

        // 📲 FUTURO: WhatsApp / Email / Push
    }

    private function notifyReschedule($appointment, $note)
    {
        // 🔥 Aquí luego metes:
        // - Email
        // - WhatsApp
        // - Notificación interna

        // Ejemplo futuro:
        // Notification::send($appointment->staff, new AppointmentRescheduled(...));
    }
}
