<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Organización a la que pertenece
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // Usuario del sistema (si aplica)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Citas asignadas
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // Servicios generales que puede ofrecer
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_staff');
    }

    // Variantes específicas que puede ofrecer
    /*public function serviceVariants()
    {
        return $this->belongsToMany(ServiceVariant::class, 'service_variant_staff');
    }*/
    public function serviceVariants()
    {
        return $this->belongsToMany(
            ServiceVariant::class,
            'service_variant_staff', // tu tabla pivote
            'staff_id',
            'service_variant_id'
        );
    }

    // Horarios semanales
    public function schedules()
    {
        return $this->hasMany(StaffMemberSchedule::class);
    }

    // Configuración de agenda (1 a 1)
    public function agendaSetting()
    {
        return $this->hasOne(StaffMemberAgendaSetting::class);
    }

    // Días no laborables
    public function nonWorkingDays()
    {
        return $this->hasMany(StaffMemberNonWorkingDay::class);
    }

    // Bloqueos manuales
    public function blockedSlots()
    {
        return $this->hasMany(BlockedSlot::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
}
