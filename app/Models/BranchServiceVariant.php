<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchServiceVariant extends Model
{
    protected $table = 'branch_service_variant';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'service_variant_id',

        'active',
        'sort_order',

        'name',
        'description',
        'duration_minutes',
        'price',
        'max_capacity',
        'mode',
        'includes_material',
    ];

    protected $casts = [
        'active' => 'boolean',
        'includes_material' => 'boolean',
        'duration_minutes' => 'integer',
        'max_capacity' => 'integer',
        'price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /*
    |------------------------------------------------------------------
    | RELACIONES
    |------------------------------------------------------------------
    */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function variant()
    {
        return $this->belongsTo(ServiceVariant::class, 'service_variant_id');
    }

    /*
    |------------------------------------------------------------------
    | HELPERS OVERRIDE
    |------------------------------------------------------------------
    | Si el campo local es null, usa valor global de service_variants
    */

    public function getResolvedNameAttribute(): ?string
    {
        return $this->name ?? $this->variant?->name;
    }

    public function getResolvedDescriptionAttribute(): ?string
    {
        return $this->description ?? $this->variant?->description;
    }

    public function getResolvedDurationMinutesAttribute(): ?int
    {
        return $this->duration_minutes ?? $this->variant?->duration_minutes;
    }

    public function getResolvedPriceAttribute(): ?float
    {
        return $this->price ?? $this->variant?->price;
    }

    public function getResolvedMaxCapacityAttribute(): ?int
    {
        return $this->max_capacity ?? $this->variant?->max_capacity;
    }

    public function getResolvedModeAttribute(): ?string
    {
        return $this->mode ?? $this->variant?->mode;
    }

    public function getResolvedIncludesMaterialAttribute(): ?bool
    {
        return $this->includes_material ?? $this->variant?->includes_material;
    }
}
