<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentNote extends Model
{
    protected $fillable = [
        'appointment_id',
        'user_id',
        'note',
        'type'
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
