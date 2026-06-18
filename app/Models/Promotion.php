<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{

    use SoftDeletes;

    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'organization_id',
        'branch_id',

        'branch_service_id',
        'branch_service_variant_id',

        'name',
        'description',

        'discount_type',
        'discount_value',

        'priority',
        'stackable',

        'max_uses',
        'used_count',

        'starts_at',
        'ends_at',

        'is_active',

        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',

        'starts_at' => 'datetime',
        'ends_at' => 'datetime',

        'is_active' => 'boolean',
        'stackable' => 'boolean',

        'priority' => 'integer',

        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
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

        if (!$this->hasRemainingUses()) {
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

    public function hasRemainingUses(): bool
    {
        if (is_null($this->max_uses)) {
            return true;
        }

        return $this->used_count < $this->max_uses;
    }

    public function scopeActive($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
