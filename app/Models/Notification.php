<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'organization_id',
        'subsystem_id',

        // POLIMÓRFICO (no va en fillable directo)
        // 'notifiable_id',
        // 'notifiable_type',

        'user_id',

        'type',
        'event_key',

        'channel',

        'recipient',
        'recipient_name',

        'subject',
        'message',

        'template',
        'payload',

        'status',

        'attempts',
        'max_attempts',

        'priority',

        'provider',
        'provider_message_id',

        'error_message',
        'failed_at',

        'scheduled_at',
        'sent_at',

        'cost',
    ];

    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'cost' => 'decimal:4',
    ];

    // =========================
    // RELACIONES
    // =========================

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function subsystem()
    {
        return $this->belongsTo(Subsystem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELACIÓN POLIMÓRFICA
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    // =========================
    // SCOPES
    // =========================

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeBySubsystem($query, $subsystemId)
    {
        return $query->where('subsystem_id', $subsystemId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // =========================
    // HELPERS (PRO LEVEL)
    // =========================

    public function markAsSent($provider = null, $providerMessageId = null)
    {
        $this->update([
            'status' => 'sent',
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed($error)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'failed_at' => now(),
            'attempts' => $this->attempts + 1,
        ]);
    }
}
