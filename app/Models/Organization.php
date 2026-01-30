<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    // use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'owner_user_id',
        'status',
        'phone',
        'email',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'phone' => 'array',
    ];

    /* =====================
     |  Relaciones
     ===================== */

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /* =====================
     |  Scopes útiles
     ===================== */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /****************************************** */
    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot(['role', 'status', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    /****************************************** */
    public function subsystems()
    {
        return $this->hasMany(OrganizationSubsystem::class);
    }
    
}
