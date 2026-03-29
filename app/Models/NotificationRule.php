<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    protected $fillable = [
        'organization_id',

        'type',
        'channel',

        'recipient_type',
        'custom_recipients',

        'is_enabled',
        'delay_minutes',

        'template_id',

        'max_per_day',
        'max_per_month',
    ];

    protected $casts = [
        'custom_recipients' => 'array',
        'is_enabled' => 'boolean',
    ];

    // =========================
    // RELACIONES
    // =========================

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    // =========================
    // SCOPES
    // =========================

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
