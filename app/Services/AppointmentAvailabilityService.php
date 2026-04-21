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
use Exception;

class AppointmentAvailabilityService
{
    /*
    |--------------------------------------------------------------------------
    | CHECK SIMPLE (USO PÚBLICO)
    |--------------------------------------------------------------------------
    */
    public function isStaffAvailable($staffId, $branchId, Carbon $start, Carbon $end): bool
    {
        // 0. Validar que el staff pertenece a la sucursal
        $staff = StaffMember::find($staffId);

        if (!$staff || !$staff->belongsToBranch($branchId)) {
            return false;
        }

        /*
        |----------------------------------------------------------
        | 1. CITAS
        |----------------------------------------------------------
        */
        $hasConflict = Appointment::where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->whereIn('status', ['pending', 'confirmed', 'rescheduled'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();

        if ($hasConflict) return false;

        /*
        |----------------------------------------------------------
        | 2. BLOQUEOS (GLOBAL + STAFF)
        |----------------------------------------------------------
        */
        $isBlocked = BlockedSlot::where('branch_id', $branchId)
            ->where(function ($q) use ($staffId) {
                $q->where('staff_member_id', $staffId)
                    ->orWhereNull('staff_member_id');
            })
            ->where(function ($q) use ($start, $end) {
                $q->where('start_datetime', '<', $end)
                    ->where('end_datetime', '>', $start);
            })
            ->exists();

        if ($isBlocked) return false;

        /*
        |----------------------------------------------------------
        | 3. NON WORKING DAY
        |----------------------------------------------------------
        */
        $isNonWorkingDay = StaffMemberNonWorkingDay::where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->whereDate('date', $start)
            ->exists();

        if ($isNonWorkingDay) return false;

        /*
        |----------------------------------------------------------
        | 4. SCHEDULE (MULTI BLOQUE)
        |----------------------------------------------------------
        */
        $dayOfWeek = $start->dayOfWeek;

        $schedules = StaffMemberSchedule::where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) return false;

        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes   = $end->hour * 60 + $end->minute;

        $withinSchedule = false;

        foreach ($schedules as $schedule) {
            $s = Carbon::parse($schedule->start_time);
            $e = Carbon::parse($schedule->end_time);

            $workStart = $s->hour * 60 + $s->minute;
            $workEnd   = $e->hour * 60 + $e->minute;

            if ($startMinutes >= $workStart && $endMinutes <= $workEnd) {
                $withinSchedule = true;
                break;
            }
        }

        if (!$withinSchedule) return false;

        /*
        |----------------------------------------------------------
        | 5. RECURRING BLOCKS
        |----------------------------------------------------------
        */
        $recurring = DB::table('staff_recurring_blocks')
            ->where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->get();

        foreach ($recurring as $r) {
            $rStart = Carbon::parse($r->start_time);
            $rEnd   = Carbon::parse($r->end_time);

            $rStartMin = $rStart->hour * 60 + $rStart->minute;
            $rEndMin   = $rEnd->hour * 60 + $rEnd->minute;

            if ($startMinutes < $rEndMin && $endMinutes > $rStartMin) {
                return false;
            }
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | GET AVAILABLE STAFF (POR VARIANTE + SUCURSAL)
    |--------------------------------------------------------------------------
    */
    public function getAvailableStaff($serviceVariantId, $branchId, Carbon $start, Carbon $end)
    {
        $variant = ServiceVariant::find($serviceVariantId);

        if (!$variant) {
            return collect();
        }

        return $variant->staff()
            ->wherePivot('branch_id', $branchId)
            ->get()
            ->filter(
                fn($staff) =>
                $this->isStaffAvailable($staff->id, $branchId, $start, $end)
            );
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN COMPLETA (LANZA ERRORES)
    |--------------------------------------------------------------------------
    */
    public function validateOrFail($staffId, $branchId, Carbon $start, Carbon $end, $ignoreBlockId = null): void
    {
        if (!$start->isSameDay($end)) {
            throw new Exception('multi_day_not_allowed');
        }

        // pertenece a sucursal
        $staff = StaffMember::find($staffId);

        if (!$staff || !$staff->belongsToBranch($branchId)) {
            throw new Exception('staff_not_in_branch');
        }


        /*
        |----------------------------------------------------------
        | 1. CITAS
        |----------------------------------------------------------
        */
        $hasConflict = Appointment::where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
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
        | 2. BLOQUEOS
        |----------------------------------------------------------
        */
        $isBlocked = BlockedSlot::where('branch_id', $branchId)
            ->when($ignoreBlockId, fn($q) => $q->where('id', '!=', $ignoreBlockId))
            ->where(function ($q) use ($staffId) {
                $q->where('staff_member_id', $staffId)
                    ->orWhereNull('staff_member_id');
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
        | 3. NON WORKING DAY
        |----------------------------------------------------------
        */
        $isNonWorkingDay = StaffMemberNonWorkingDay::where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->whereDate('date', $start)
            ->exists();

        if ($isNonWorkingDay) {
            throw new Exception('non_working_day');
        }

        /*
        |----------------------------------------------------------
        | 4. SCHEDULE
        |----------------------------------------------------------
        */
        $dayOfWeek = $start->dayOfWeek;

        $schedules = StaffMemberSchedule::where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get();

        if ($schedules->isEmpty()) {
            throw new Exception('no_schedule');
        }

        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes   = $end->hour * 60 + $end->minute;

        $valid = false;

        foreach ($schedules as $schedule) {
            $s = Carbon::parse($schedule->start_time);
            $e = Carbon::parse($schedule->end_time);

            $workStart = $s->hour * 60 + $s->minute;
            $workEnd   = $e->hour * 60 + $e->minute;

            if ($startMinutes >= $workStart && $endMinutes <= $workEnd) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            throw new Exception('outside_working_hours');
        }

        /*
        |----------------------------------------------------------
        | 5. RECURRING BLOCKS
        |----------------------------------------------------------
        */
        $recurring = DB::table('staff_recurring_blocks')
            ->where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->where('day_of_week', $dayOfWeek)
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
