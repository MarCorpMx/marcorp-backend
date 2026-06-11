<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Branch;
use App\Models\BranchServiceVariantStaff;
use App\Models\StaffMember;
use App\Models\StaffMemberNonWorkingDay;
use App\Models\StaffMemberSchedule;
use App\Models\StaffRecurringBlock;
use Carbon\Carbon;
use Exception;

class AppointmentAvailabilityService
{
    public function __construct(
        protected AppointmentTimezoneService $timezoneService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | CHECK SIMPLE
    |--------------------------------------------------------------------------
    */
    public function isStaffAvailable(
        int $staffId,
        int $branchId,
        Carbon $startUtc,
        Carbon $endUtc
    ): bool {

        try {

            $this->validateOrFail(
                $staffId,
                $branchId,
                $startUtc,
                $endUtc
            );

            return true;
        } catch (\Throwable $e) {

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET AVAILABLE STAFF
    |--------------------------------------------------------------------------
    */
    public function getAvailableStaff(
        int $branchServiceVariantId,
        int $branchId,
        Carbon $startUtc,
        Carbon $endUtc
    ) {

        $assignments = BranchServiceVariantStaff::query()
            ->active()
            ->where('branch_service_variant_id', $branchServiceVariantId)
            ->where('branch_id', $branchId)
            ->with('staffMember')
            ->get();

        return $assignments
            ->pluck('staffMember')
            ->filter()
            ->filter(
                fn($staff) =>
                $this->isStaffAvailable(
                    $staff->id,
                    $branchId,
                    $startUtc,
                    $endUtc
                )
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDACIÓN COMPLETA
    |--------------------------------------------------------------------------
    */
    public function validateOrFail(
        int $staffId,
        int $branchId,
        Carbon $startUtc,
        Carbon $endUtc,
        ?int $ignoreBlockId = null,
        bool $strictAvailability = true
    ): void {

        $staff = StaffMember::find($staffId);

        if (
            !$staff ||
            !$staff->belongsToBranch($branchId)
        ) {
            throw new Exception('staff_not_in_branch');
        }

        $branch = Branch::findOrFail($branchId);

        $timezone =
            $this->timezoneService
            ->resolveBranchTimezone($branch);

        /*
        |--------------------------------------------------------------------------
        | UTC -> LOCAL
        |--------------------------------------------------------------------------
        */
        $localStart =
            $this->timezoneService
            ->utcToLocal(
                $startUtc,
                $timezone
            );

        $localEnd =
            $this->timezoneService
            ->utcToLocal(
                $endUtc,
                $timezone
            );

        /*
        |--------------------------------------------------------------------------
        | NO MULTI DAY
        |--------------------------------------------------------------------------
        */
        if (
            !$localStart->isSameDay($localEnd)
        ) {
            throw new Exception(
                'multi_day_not_allowed'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 1. APPOINTMENTS
        |--------------------------------------------------------------------------
        |
        | UTC vs UTC
        |
        */
        $appointmentConflict =
            Appointment::query()
            ->where('staff_member_id', $staffId)
            ->where('branch_id', $branchId)
            ->whereIn('status', [
                'pending',
                'confirmed',
                'rescheduled'
            ])
            ->where(
                'start_datetime',
                '<',
                $endUtc
            )
            ->where(
                'end_datetime',
                '>',
                $startUtc
            )
            ->exists();

        if ($appointmentConflict) {
            throw new Exception(
                'appointment_conflict'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 2. BLOCKED SLOTS
        |--------------------------------------------------------------------------
        |
        | UTC vs UTC
        |
        */
        $blocked =
            BlockedSlot::query()
            ->where('branch_id', $branchId)
            ->when(
                $ignoreBlockId,
                fn($q) =>
                $q->where(
                    'id',
                    '!=',
                    $ignoreBlockId
                )
            )
            ->where(function ($q) use ($staffId) {

                $q->where(
                    'staff_member_id',
                    $staffId
                )
                    ->orWhereNull(
                        'staff_member_id'
                    );
            })
            ->where(
                'start_datetime',
                '<',
                $endUtc
            )
            ->where(
                'end_datetime',
                '>',
                $startUtc
            )
            ->exists();

        if ($blocked) {
            throw new Exception(
                'manual_block_conflict'
            );
        }

        /************************* IMPORTANT *************************/
        // No seguimos con validaciones si no se necesitan (admin_panel por ejemplo)
        if (!$strictAvailability) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | 3. NON WORKING DAY
        |--------------------------------------------------------------------------
        |
        | LOCAL DATE
        |
        */
        $nonWorkingDay =
            StaffMemberNonWorkingDay::query()
            ->where(
                'staff_member_id',
                $staffId
            )
            ->where(
                'branch_id',
                $branchId
            )
            ->whereDate(
                'date',
                $localStart->toDateString()
            )
            ->exists();

        if ($nonWorkingDay) {
            throw new Exception(
                'non_working_day'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 4. SCHEDULE
        |--------------------------------------------------------------------------
        |
        | LOCAL TIME
        |
        */
        $dayOfWeek =
            $localStart->dayOfWeek;

        $schedules =
            StaffMemberSchedule::query()
            ->where(
                'staff_member_id',
                $staffId
            )
            ->where(
                'branch_id',
                $branchId
            )
            ->where(
                'day_of_week',
                $dayOfWeek
            )
            ->where(
                'is_active',
                true
            )
            ->get();

        if ($schedules->isEmpty()) {
            throw new Exception(
                'no_schedule'
            );
        }

        $startMinutes =
            ($localStart->hour * 60)
            + $localStart->minute;

        $endMinutes =
            ($localEnd->hour * 60)
            + $localEnd->minute;

        $insideSchedule = false;

        foreach ($schedules as $schedule) {

            $scheduleStart =
                Carbon::createFromFormat(
                    'H:i:s',
                    $schedule->start_time
                );

            $scheduleEnd =
                Carbon::createFromFormat(
                    'H:i:s',
                    $schedule->end_time
                );

            $scheduleStartMinutes =
                ($scheduleStart->hour * 60)
                + $scheduleStart->minute;

            $scheduleEndMinutes =
                ($scheduleEnd->hour * 60)
                + $scheduleEnd->minute;

            if (
                $startMinutes >=
                $scheduleStartMinutes
                &&
                $endMinutes <=
                $scheduleEndMinutes
            ) {
                $insideSchedule = true;
                break;
            }
        }

        if (!$insideSchedule) {
            throw new Exception(
                'outside_working_hours'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. RECURRING BLOCKS
        |--------------------------------------------------------------------------
        |
        | LOCAL TIME
        |
        */
        $blocks =
            StaffRecurringBlock::query()
            ->where(
                'staff_member_id',
                $staffId
            )
            ->where(
                'branch_id',
                $branchId
            )
            ->where(
                'day_of_week',
                $dayOfWeek
            )
            ->get();

        foreach ($blocks as $block) {

            $blockStart =
                Carbon::createFromFormat(
                    'H:i:s',
                    $block->start_time
                );

            $blockEnd =
                Carbon::createFromFormat(
                    'H:i:s',
                    $block->end_time
                );

            $blockStartMinutes =
                ($blockStart->hour * 60)
                + $blockStart->minute;

            $blockEndMinutes =
                ($blockEnd->hour * 60)
                + $blockEnd->minute;

            if (
                $startMinutes <
                $blockEndMinutes
                &&
                $endMinutes >
                $blockStartMinutes
            ) {
                throw new Exception(
                    'recurring_block_conflict'
                );
            }
        }
    }
}
