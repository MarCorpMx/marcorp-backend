<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationScheduleSetting extends Model
{
    // =====================
    // Fillable
    // =====================
    protected $fillable = [
        'organization_id',
        'default_appointment_duration',
        'break_between_appointments',
        'rules',
        'working_hours',
        'holidays',
    ];

    // =====================
    // Casts
    // =====================
    protected $casts = [
        'rules' => 'array',
        'working_hours' => 'array',
        'holidays' => 'array',
    ];

    // =====================
    // Relaciones
    // =====================
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // =====================
    // Helpers
    // =====================
    public function getWorkingHoursForDay(string $day): array
    {
        return $this->working_hours[$day] ?? [];
    }

    public function isHoliday(string $date): bool
    {
        return in_array($date, $this->holidays ?? []);
    }
}