<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Exception;

class AppointmentService
{
    public function create(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {

            $exists = Appointment::where('organization_id', $data['organization_id'])
                ->where('staff_member_id', $data['staff_member_id'])
                ->whereIn('status', ['pending','confirmed'])
                ->where(function ($q) use ($data) {
                    $q->where('start_datetime', '<', $data['end_datetime'])
                      ->where('end_datetime', '>', $data['start_datetime']);
                })
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                throw new Exception('Horario no disponible');
            }

            return Appointment::create($data);
        });
    }
}