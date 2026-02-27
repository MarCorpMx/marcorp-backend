<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgendaSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'professional_id',
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
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }
}
