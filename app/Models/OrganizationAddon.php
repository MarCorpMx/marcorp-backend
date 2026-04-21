<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizationAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'key',
        'quantity',
        'status',
        'starts_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status === 'active' &&
            (!$this->expires_at || $this->expires_at->isFuture());
    }
}
