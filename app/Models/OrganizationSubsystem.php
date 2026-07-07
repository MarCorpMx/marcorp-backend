<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationSubsystem extends Model
{
    protected $table = 'organization_subsystems';


    protected $fillable = [
        'organization_id',
        'subsystem_id',
        'plan_id',
        'status',
        'started_at',
        'expires_at',
        'renews_at',
        'cancelled_at',
        'is_paid',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'renews_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'is_paid' => 'boolean',
        'metadata' => 'array',
    ];


    // constantes
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';

    /* =====================
     | Relaciones
     ===================== */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function subsystem()
    {
        return $this->belongsTo(Subsystem::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /* =====================
     | Scopes útiles
     ===================== */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_TRIAL
        ]);
    }

    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return now()->greaterThan($this->expires_at);
    }
}
