<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchUserAccess extends Model
{
    protected $table = 'branch_user_access';

    protected $fillable = [
        'organization_id',
        'user_id',
        'branch_id',
        'subsystem_id',
        'role_id',
        'staff_member_id',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function subsystem()
    {
        return $this->belongsTo(Subsystem::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForSubsystem($query, $subsystemId)
    {
        return $query->where('subsystem_id', $subsystemId);
    }
}
