<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanSubsystemFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'subsystem_id',
        'feature_id',
        'is_enabled',
        'limit_value',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subsystem()
    {
        return $this->belongsTo(Subsystem::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}
