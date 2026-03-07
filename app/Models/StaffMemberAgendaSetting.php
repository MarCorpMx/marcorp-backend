<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMemberAgendaSetting extends Model
{
    use HasFactory;

    protected $table = 'staff_member_agenda_settings';

    protected $fillable = [
        'staff_member_id',
        'appointment_duration',
        'break_between_appointments',
        'allow_online_booking',
        'minimum_notice_hours',
        'allow_cancellation',
        'cancellation_limit_hours',
        'timezone',
    ];

    protected $casts = [
        'allow_online_booking' => 'boolean',
        'allow_cancellation' => 'boolean',
        'appointment_duration' => 'integer',
        'break_between_appointments' => 'integer',
        'minimum_notice_hours' => 'integer',
        'cancellation_limit_hours' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }
}
