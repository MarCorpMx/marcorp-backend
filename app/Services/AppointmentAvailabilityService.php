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

    /*public function createAppointment($data)
    {
        $start = Carbon::parse($data['start']);
        $duration = ServiceVariant::find($data['service_variant_id'])->duration_minutes;

        $end = $start->copy()->addMinutes($duration);

        // 👇 SI ES ADMIN → saltar reglas duras
        if ($data['source'] === 'admin_panel') {
            return Appointment::create([
                ...$data,
                'start_datetime' => $start,
                'end_datetime'   => $end,
            ]);
        }

        $availableStaff = $this->getAvailableStaff(
            $data['service_variant_id'],
            $start,
            $end
        );

        if ($availableStaff->isEmpty()) {
            throw new Exception("No hay disponibilidad");
        }

        $staff = $availableStaff->first();

        return Appointment::create([
            ...$data,
            'staff_member_id' => $staff->id,
            'start_datetime'  => $start,
            'end_datetime'    => $end,
            'status'          => 'confirmed'
        ]);
    }*/
}
