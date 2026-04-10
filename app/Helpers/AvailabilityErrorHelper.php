<?php

namespace App\Helpers;

class AvailabilityErrorHelper
{
    public static function map($code)
    {
        return [
            'appointment_conflict' => 'Existe una cita en ese horario',
            'manual_block_conflict' => 'Existe un bloqueo en ese horario',
            'recurring_block_conflict' => 'Existe un horario bloqueado recurrentemente',
            'outside_working_hours' => 'Fuera del horario laboral',
            'non_working_day' => 'No es día laboral',
            'no_schedule' => 'No hay horario configurado',
            'multi_day_not_allowed' => 'Solo puedes crear bloqueos dentro de un mismo día',
        ][$code] ?? 'Horario no disponible';
    }
}

