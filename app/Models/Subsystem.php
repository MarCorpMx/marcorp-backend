<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subsystem extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'is_active',
        'is_selectable',
    ];


    /************************************************ */
    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_subsystems')
            ->withPivot([
                'plan_id',
                'status',
                'started_at',
                'expires_at',
                'cancelled_at',
                'is_paid',
                'metadata',
            ])
            ->withTimestamps();
    }
}
