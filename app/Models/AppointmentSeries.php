<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentSeries extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'organization_id',
        'branch_id',

        'frequency',
        'interval',
        'days_of_week',

        'start_datetime',
        'until_datetime',

        'occurrences_limit',

        'is_active',
        'last_generated_at',
    ];

    protected $casts = [
        'days_of_week' => 'array',

        'start_datetime' => 'datetime',
        'until_datetime' => 'datetime',

        'last_generated_at' => 'datetime',

        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
