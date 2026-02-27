<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ServiceVariant extends Model
{
    protected $fillable = [
        'service_id',
        'name',
        'duration_minutes',
        'price',
        'max_capacity',
        'mode',
        'includes_material',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'includes_material' => 'boolean',
        'active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function organization(): BelongsTo
    {
        return $this->service->organization();
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(
            StaffMember::class,
            'service_variant_staff',
            'service_variant_id',
            'staff_id'
        )->withTimestamps();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}