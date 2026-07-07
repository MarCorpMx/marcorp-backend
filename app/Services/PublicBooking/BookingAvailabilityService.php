<?php

namespace App\Services\PublicBooking;

use App\Models\BranchServiceVariant;
use App\Models\BranchServiceVariantStaff;
use App\Models\StaffMember;
use App\Models\StaffMemberSchedule;
use App\Models\StaffMemberNonWorkingDay;

use App\Services\AppointmentAvailabilityService;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class BookingAvailabilityService
{

    public function __construct(
        protected AppointmentAvailabilityService $appointmentAvailability
    ) {}

    private const DAYS_TO_GENERATE = 30;

    public function getAvailability(
        BranchServiceVariant $variant,
        ?int $staffMemberId = null
    ) {
        $variant->loadMissing('branch');

        $staffMembers = $this->resolveStaffMembers(
            $variant,
            $staffMemberId
        );

        if ($staffMemberId && $staffMembers->isEmpty()) {
            abort(404);
        }

        $timezone = $this->resolveTimezone($variant);

        return [
            'timezone' => $timezone,
            'calendar' => $this->generateCalendar(
                $staffMembers,
                $variant,
                $timezone
            ),
        ];
    }

    private function resolveStaffMembers(
        BranchServiceVariant $variant,
        ?int $staffMemberId = null
    ): Collection {

        if ($staffMemberId) {

            $assignment = BranchServiceVariantStaff::query()
                ->active()
                ->where('branch_service_variant_id', $variant->id)
                ->where('branch_id', $variant->branch_id)
                ->where('staff_member_id', $staffMemberId)
                //->with('staffMember')
                ->with([
                    'staffMember.agendaSettings' => function ($query) use ($variant) {
                        $query->where('branch_id', $variant->branch_id);
                    }
                ])
                ->first();

            if (!$assignment) {
                return collect();
            }

            return collect([
                $assignment->staffMember
            ]);
        }

        return BranchServiceVariantStaff::query()
            ->active()
            ->where('branch_service_variant_id', $variant->id)
            ->where('branch_id', $variant->branch_id)
            ->with([
                'staffMember.agendaSettings' => function ($query) use ($variant) {
                    $query->where('branch_id', $variant->branch_id);
                }
            ])
            ->get()
            ->pluck('staffMember')
            ->filter()
            ->values();
    }

    private function resolveTimezone(
        BranchServiceVariant $variant
    ): string {

        return $variant->branch->timezone
            ?? config('app.timezone');
    }

    private function generateCalendar(
        Collection $staffMembers,
        BranchServiceVariant $variant,
        string $timezone
    ): array {

        $calendar = [];

        $today = Carbon::now($timezone)->startOfDay();

        for ($i = 0; $i < self::DAYS_TO_GENERATE; $i++) {

            $date = $today->copy()->addDays($i);

            $enabled = false;

            foreach ($staffMembers as $staff) {

                if ($this->isWorkingDay(
                    $staff,
                    $variant,
                    $date
                )) {

                    $enabled = true;
                    break;
                }
            }

            $calendar[] = [

                'date' => $date->toDateString(),

                'day_of_week' => $date->dayOfWeek,

                'enabled' => $enabled

            ];
        }

        return $calendar;
    }

    private function isWorkingDay(
        StaffMember $staff,
        BranchServiceVariant $variant,
        Carbon $date
    ): bool {

        $dayOfWeek = $date->dayOfWeek;

        $hasSchedule = StaffMemberSchedule::query()
            ->where('staff_member_id', $staff->id)
            ->where('branch_id', $variant->branch_id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->exists();

        if (! $hasSchedule) {
            return false;
        }

        $isNonWorkingDay = StaffMemberNonWorkingDay::query()
            ->where('staff_member_id', $staff->id)
            ->where('branch_id', $variant->branch_id)
            ->whereDate('date', $date->toDateString())
            ->exists();

        return ! $isNonWorkingDay;
    }

    private function generateDates() {}


    // para las horas man
    public function getTimeSlots(
        BranchServiceVariant $variant,
        Carbon $date,
        ?int $staffMemberId = null
    ): array {

        $variant->loadMissing('branch');

        $staffMembers = $this->resolveStaffMembers(
            $variant,
            $staffMemberId
        );

        if ($staffMemberId && $staffMembers->isEmpty()) {
            abort(404);
        }

        return $this->generateTimeSlots(
            $staffMembers,
            $variant,
            $date
        );
    }

    // para las horas man
    private function generateTimeSlots(
        Collection $staffMembers,
        BranchServiceVariant $variant,
        Carbon $date
    ): array {

        $slots = [];

        foreach ($staffMembers as $staff) {

            $settings = $staff->agendaSettingForBranch(
                $variant->branch_id
            );


            $appointmentDuration =
                $variant->duration_minutes;

            $slotInterval =
                $settings?->appointment_duration
                ?? $appointmentDuration;

            $breakBetweenAppointments =
                $settings?->break_between_appointments
                ?? 0;

            $schedule = StaffMemberSchedule::query()
                ->where('staff_member_id', $staff->id)
                ->where('branch_id', $variant->branch_id)
                ->where('day_of_week', $date->dayOfWeek)
                ->where('is_active', true)
                ->first();

            if (!$schedule) {
                continue;
            }

            $branchTimezone = $variant->branch->timezone;

            $start = Carbon::parse(
                $date->toDateString() . ' ' . $schedule->start_time,
                $branchTimezone
            );

            $end = Carbon::parse(
                $date->toDateString() . ' ' . $schedule->end_time,
                $branchTimezone
            );


            /*dump([
                'branch_timezone' => $branchTimezone,

                'start_local' => $start->toDateTimeString(),
                'start_local_tz' => $start->timezoneName,

                'start_utc' => $start->copy()->utc()->toDateTimeString(),

                'end_local' => $end->toDateTimeString(),
                'end_local_tz' => $end->timezoneName,

                'end_utc' => $end->copy()->utc()->toDateTimeString(),
            ]);*/

            while (
                $start->copy()
                ->addMinutes($appointmentDuration)
                ->lte($end)
            ) {

                $slotEnd = $start
                    ->copy()
                    ->addMinutes($appointmentDuration);


                $available = $this->appointmentAvailability
                    ->isStaffAvailable(
                        $staff->id,
                        $variant->branch_id,
                        $start->copy()->utc(),
                        $slotEnd->copy()->utc()
                    );

                $slots[] = [

                    'time' => $start->format('H:i'),

                    'available' => $available

                ];

                $start->addMinutes(
                    $slotInterval
                );
            }
        }

        return collect($slots)
            ->unique('time')
            ->sortBy('time')
            ->values()
            ->all();
    }
}
