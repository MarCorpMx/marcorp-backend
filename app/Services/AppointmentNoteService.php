<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentNote;
use Carbon\Carbon;

class AppointmentNoteService
{
    /*
    |--------------------------------------------------------------------------
    | CREAR NOTA
    |--------------------------------------------------------------------------
    */

    public function create(
        Appointment $appointment,
        ?int $userId,
        string $type,
        ?string $note = null
    ): void {

        AppointmentNote::create([
            'appointment_id' => $appointment->id,
            'user_id' => $userId,
            'type' => $type,
            'note' => $note,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NOTA DE REAGENDADO
    |--------------------------------------------------------------------------
    */

    public function buildRescheduleNote(
        array $data,
        Carbon $oldDate,
        Carbon $newDate
    ): string {

        $parts = [];

        $parts[] =
            "Reagendada de {$oldDate->format('Y-m-d H:i')} a "
            . $newDate->format('Y-m-d H:i');

        if (!empty($data['reason'])) {
            $parts[] = "Motivo: {$data['reason']}";
        }

        if (!empty($data['note'])) {
            $parts[] = "Comentario: {$data['note']}";
        }

        return implode(' | ', $parts);
    }
}
