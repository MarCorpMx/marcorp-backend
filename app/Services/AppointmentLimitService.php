<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Organization;

class AppointmentLimitService
{
    public function validateLimitCanCreate(
        Organization $organization,
        int|string|null $limit
    ): void {

        // ilimitado
        if (
            $limit === null ||
            $limit === -1 ||
            $limit === 'unlimited'
        ) {
            return;
        }

        $currentMonth = now()->startOfMonth();

        /*$used = Appointment::query()
            ->where('organization_id', $organization->id)
            ->where('created_at', '>=', $currentMonth)
            ->count();*/

        $used = Appointment::query()
            ->where('organization_id', $organization->id)
            ->whereBetween('created_at', [
                now()->startOfMonth(),
                now()->endOfMonth()
            ])
            ->count();

        if ($used >= $limit) {
            abort(
                422,
                "Has alcanzado el límite mensual de citas de tu plan."
            );
        }
    }
}
