<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\StaffMember;
use App\Models\StaffMemberNonWorkingDay;
use App\Models\StaffMemberSchedule;
use App\Models\ServiceVariant;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;


class AppointmentAvailabilityService
{

    // Qué staff esta disponible / availability pública
    public function isStaffAvailable($staffId, $start, $end): bool
    {
        // 1. Verificar citas existentes
        $hasConflict = Appointment::where('staff_member_id', $staffId)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();
        if ($hasConflict) return false;

        // 2. Verificar bloqueos manuales
        $isBlocked = BlockedSlot::where('staff_member_id', $staffId)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();

        if ($isBlocked) return false;

        // 3. Verificar días no laborales
        $isNonWorkingDay = StaffMemberNonWorkingDay::where('staff_member_id', $staffId)
            ->whereDate('date', $start)
            ->exists();

        if ($isNonWorkingDay) return false;

        // 4. Verificar horario base
        $dayOfWeek = $start->dayOfWeek; // 0 = domingo, 1 = lunes ... 6 = sábado

        $schedule = StaffMemberSchedule::where('staff_member_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) return false;


        // Comparar solo horas
        $startHour = (int)substr($schedule->start_time, 0, 2);
        $startMinute = (int)substr($schedule->start_time, 3, 2);

        $endHour = (int)substr($schedule->end_time, 0, 2);
        $endMinute = (int)substr($schedule->end_time, 3, 2);

        $scheduleStart = (clone $start)->setTime($startHour, $startMinute);
        $scheduleEnd   = (clone $start)->setTime($endHour, $endMinute);

        // DEBUG
        //Log::info("Comparando start={$start} end={$end} scheduleStart={$scheduleStart} scheduleEnd={$scheduleEnd}");

        return $start >= $scheduleStart && $end <= $scheduleEnd;
    }

    public function getAvailableStaff($serviceVariantId, $start, $end)
    {
        $staffList = DB::table('service_variant_staff')
            ->where('service_variant_id', $serviceVariantId)
            ->pluck('staff_member_id');

        return StaffMember::whereIn('id', $staffList)
            ->get()
            ->filter(fn($staff) => $this->isStaffAvailable($staff->id, $start, $end));
    }

    // rombi - Nuevo
    public function validateOrFail($staffId, $start, $end, $ignoreBlockId = null): void
    {

        // Solo se puede sobre un mismo día
        if (!$start->isSameDay($end)) {
            throw new Exception('multi_day_not_allowed');
        }

        /*
        |----------------------------------------------------------
        | 1. CITAS
        |----------------------------------------------------------
        */
        $hasConflict = Appointment::where('staff_member_id', $staffId)
            ->whereIn('status', ['pending', 'confirmed', 'rescheduled'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();

        if ($hasConflict) {
            throw new Exception('appointment_conflict');
        }

        /*
        |----------------------------------------------------------
        | 2. BLOQUES MANUALES
        |----------------------------------------------------------
        */
        /*$isBlocked = BlockedSlot::where('staff_member_id', $staffId)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();*/

        $isBlocked = BlockedSlot::where('staff_member_id', $staffId)
            ->when($ignoreBlockId, function ($q) use ($ignoreBlockId) {
                $q->where('id', '!=', $ignoreBlockId);
            })
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();

        if ($isBlocked) {
            throw new Exception('manual_block_conflict');
        }

        /*
        |----------------------------------------------------------
        | 3. DÍAS NO LABORALES
        |----------------------------------------------------------
        */
        $isNonWorkingDay = StaffMemberNonWorkingDay::where('staff_member_id', $staffId)
            ->whereDate('date', $start)
            ->exists();

        if ($isNonWorkingDay) {
            throw new Exception('non_working_day');
        }

        /*
        |----------------------------------------------------------
        | 4. HORARIO LABORAL
        |----------------------------------------------------------
        */
        $dayOfWeek = $start->dayOfWeek;

        $schedule = StaffMemberSchedule::where('staff_member_id', $staffId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$schedule) {
            throw new Exception('no_schedule');
        }

        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd   = Carbon::parse($schedule->end_time);

        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes   = $end->hour * 60 + $end->minute;

        $workStart = $scheduleStart->hour * 60 + $scheduleStart->minute;
        $workEnd   = $scheduleEnd->hour * 60 + $scheduleEnd->minute;

        if ($startMinutes < $workStart || $endMinutes > $workEnd) {
            throw new Exception('outside_working_hours');
        }

        /*
        |----------------------------------------------------------
        | 5. BLOQUES RECURRENTES 
        |----------------------------------------------------------
        */
        $day = $start->dayOfWeek; // 0–6 (0 = dominguito alegre)

        $recurring = DB::table('staff_recurring_blocks')
            ->where('staff_member_id', $staffId)
            ->where('day_of_week', $day)
            ->get();

        foreach ($recurring as $r) {

            $rStart = Carbon::parse($r->start_time);
            $rEnd   = Carbon::parse($r->end_time);

            $rStartMin = $rStart->hour * 60 + $rStart->minute;
            $rEndMin   = $rEnd->hour * 60 + $rEnd->minute;

            if ($startMinutes < $rEndMin && $endMinutes > $rStartMin) {
                throw new Exception('recurring_block_conflict');
            }
        }
    }
}
