<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Appointment extends Model
{
    protected $fillable = [
        'organization_id',
        'service_variant_id',
        'staff_member_id',
        'client_id',
        'start_datetime',
        'end_datetime',
        'status',
        'source',
        'notes',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function serviceVariant(): BelongsTo
    {
        return $this->belongsTo(ServiceVariant::class);
    }

    /*public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_id');
    }*/
    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    // Para citas individuales
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // Para citas grupales
    public function attendees(): HasMany
    {
        return $this->hasMany(AppointmentAttendee::class);
    }

    /******************** */
    protected static function booted()
    {
        static::creating(function ($appointment) {
            $appointment->uuid = Str::uuid();
        });
    }
}
