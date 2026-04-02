<?php

namespace App\Observers;

use App\Models\Organization;
use App\Models\StaffMember;
use App\Models\StaffMemberAgendaSetting;
use App\Models\StaffMemberSchedule;

class OrganizationObserver
{
    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        //$owner = $organization->owner;

        $owner = $organization->owner()->first();

        if (!$owner) {
            return;
        }

        // 1 Crear StaffMember
        /*$staff = StaffMember::create([
            'organization_id' => $organization->id,
            'user_id' => $owner->id,
            'name' => $owner->name,
            'email' => $owner->email,
            'is_active' => true,
        ]);*/
        $staff = StaffMember::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'user_id' => $owner->id,
            ],
            [
                'name' => $owner->name,
                'email' => $owner->email,
                'is_active' => true,
            ]
        );

        // 2 Crear configuración básica de agenda
        /*$staff->agendaSetting()->create([
            'appointment_duration' => 60,
            'break_between_appointments' => 0,
            'minimum_notice_hours' => 2,
            'cancellation_limit_hours' => 12,
            'allow_online_booking' => true,
            'allow_cancellation' => true,
            'timezone' => 'America/Mexico_City',
        ]);*/

        $staff->agendaSetting()->firstOrCreate(
            [],
            [
                'appointment_duration' => 60,
                'break_between_appointments' => 0,
                'minimum_notice_hours' => 2,
                'cancellation_limit_hours' => 12,
                'allow_online_booking' => true,
                'allow_cancellation' => true,
                'timezone' => 'America/Mexico_City',
            ]
        );

        // 3 Crear horario Lunes a Viernes 8am–6pm
        $defaultDays = [1, 2, 3, 4, 5]; // lunes a viernes

        foreach ($defaultDays as $day) {
            $staff->schedules()->create([
                'day_of_week' => $day,
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
            ]);
        }
    }

    /**
     * Handle the Organization "updated" event.
     */
    public function updated(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "deleted" event.
     */
    public function deleted(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "restored" event.
     */
    public function restored(Organization $organization): void
    {
        //
    }

    /**
     * Handle the Organization "force deleted" event.
     */
    public function forceDeleted(Organization $organization): void
    {
        //
    }
}
