<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'organization_id',
        'branch_service_id',
        'branch_service_variant_id',

        'name',
        'discount_type',
        'discount_value',

        'starts_at',
        'ends_at',

        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'is_active'      => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function service()
    {
        return $this->belongsTo(
            BranchService::class,
            'branch_service_id'
        );
    }

    public function serviceVariant()
    {
        return $this->belongsTo(
            BranchServiceVariant::class,
            'branch_service_variant_id'
        );
    }

    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $basePrice): float
    {
        if ($this->discount_type === self::TYPE_FIXED) {
            return min($this->discount_value, $basePrice);
        }

        if ($this->discount_type === self::TYPE_PERCENTAGE) {
            return ($basePrice * $this->discount_value) / 100;
        }

        return 0;
    }
}
