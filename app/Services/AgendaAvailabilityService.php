<?php

namespace App\Services;

use App\Models\Professional;
use Carbon\Carbon;

class AgendaAvailabilityService
{
    public function getAvailableSlots(Professional $professional, string $date)
    {
        $date = Carbon::parse($date);
        $dayOfWeek = $date->dayOfWeek; // 0 domingo, 6 sábado

        // 1️⃣ Verificar día no laborable
        $isNonWorking = $professional->nonWorkingDays()
            ->whereDate('date', $date)
            ->exists();

        if ($isNonWorking) {
            return [];
        }

        // 2️⃣ Obtener horarios activos del día
        $schedules = $professional->schedules()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            return [];
        }

        $settings = $professional->agendaSettings;

        $duration = $settings->appointment_duration;
        $break = $settings->break_between_appointments;
        $minimumNotice = $settings->minimum_notice_hours;

        $slots = [];

        foreach ($schedules as $schedule) {

            $start = Carbon::parse($date->toDateString() . ' ' . $schedule->start_time);
            $end = Carbon::parse($date->toDateString() . ' ' . $schedule->end_time);

            while ($start->copy()->addMinutes($duration) <= $end) {

                // 3️⃣ Validar anticipación mínima
                if (now()->addHours($minimumNotice)->lte($start)) {
                    $slots[] = $start->format('H:i');
                }

                $start->addMinutes($duration + $break);
            }
        }

        return $slots;
    }
}
