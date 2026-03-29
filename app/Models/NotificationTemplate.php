<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'organization_id',
        'type',
        'channel',
        'name',

        'subject',
        'body',
        'body_text',

        'is_active',
        'variables',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'variables' => 'array',
    ];

    // =========================
    // RELACIONES
    // =========================

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    // =========================
    // SCOPES
    // =========================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }
}
