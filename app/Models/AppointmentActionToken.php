<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AppointmentActionToken extends Model
{
    protected $fillable = [
        'appointment_id',
        'token',
        'action',
        'expires_at',
        'used_at',
        'revoked_at',
        'used_ip',
        'used_user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    public function isRevoked(): bool
    {
        return !is_null($this->revoked_at);
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed() && !$this->isRevoked();
    }

    /*
    |--------------------------------------------------------------------------
    | Acciones
    |--------------------------------------------------------------------------
    */
    public function markAsUsed(): void
    {
        $this->update([
            'used_at' => now(),
            'used_ip' => request()->ip(),
            'used_user_agent' => request()->userAgent(),
        ]);
    }

    public function revoke(): void
    {
        $this->update([
            'revoked_at' => now()
        ]);
    }
}
