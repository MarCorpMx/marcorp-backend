<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class BranchServiceVariant extends Model
{

    use SoftDeletes;

    protected $table = 'branch_service_variant';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'branch_service_id',

        'name',
        'description',
        'duration_minutes',
        'price',
        'max_capacity',
        'mode',
        'includes_material',

        'requires_meeting_link',
        'meeting_provider',

        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'includes_material' => 'boolean',
        'requires_meeting_link' => 'boolean',
        'duration_minutes' => 'integer',
        'max_capacity' => 'integer',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function branchService()
    {
        return $this->belongsTo(
            BranchService::class,
            'branch_service_id'
        );
    }

    public function service()
    {
        return $this->belongsTo(
            BranchService::class,
            'branch_service_id'
        );
    }

    public function staffAssignments()
    {
        return $this->hasMany(
            BranchServiceVariantStaff::class,
            'branch_service_variant_id'
        );
    }

    public function appointments()
    {
        return $this->hasMany(
            Appointment::class,
            'branch_service_variant_id'
        );
    }

    public function scopeActive(Builder $query)
    {
        return $query->where('active', true);
    }
}
