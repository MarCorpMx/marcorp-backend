<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Professional extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'specialty',
        'color',
        'is_active',
        'phone',
        'email',
    ];

    protected $casts = [
        'phone'        => 'array',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function agendaSettings()
    {
        return $this->hasOne(AgendaSetting::class);
    }

    public function schedules()
    {
        return $this->hasMany(ProfessionalSchedule::class);
    }

    public function nonWorkingDays()
    {
        return $this->hasMany(NonWorkingDay::class);
    }

    // Cada vez que se cree un profesional, automáticamente tendrá configuración base.
    protected static function booted()
    {
        static::created(function ($professional) {
            $professional->agendaSettings()->create();
        });
    }
}
