<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    protected $fillable = [
        'organization_id',
        'staff_member_id',
        'start_datetime',
        'end_datetime',
        'reason',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
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

    public function staff()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes (muy útil para SaaS)
    |--------------------------------------------------------------------------
    */

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_member_id', $staffId);
    }
}