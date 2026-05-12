<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BranchServiceVariantStaff extends Model
{
    protected $table = 'branch_service_variant_staff';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'branch_service_variant_id',
        'staff_member_id',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
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

    public function branchServiceVariant()
    {
        return $this->belongsTo(
            BranchServiceVariant::class,
            'branch_service_variant_id'
        );
    }

    public function staffMember()
    {
        return $this->belongsTo(
            StaffMember::class,
            'staff_member_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeForOrganization(
        Builder $query,
        int $organizationId
    ): Builder {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeForBranch(
        Builder $query,
        int $branchId
    ): Builder {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForStaff(
        Builder $query,
        int $staffMemberId
    ): Builder {
        return $query->where('staff_member_id', $staffMemberId);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors útiles
    |--------------------------------------------------------------------------
    */

    public function getServiceNameAttribute(): ?string
    {
        return $this->branchServiceVariant?->branchService?->name;
    }

    public function getVariantNameAttribute(): ?string
    {
        return $this->branchServiceVariant?->name;
    }

    public function getFullLabelAttribute(): ?string
    {
        $service = $this->service_name;
        $variant = $this->variant_name;

        if (!$service && !$variant) {
            return null;
        }

        return trim($service . ' - ' . $variant, ' -');
    }
}
