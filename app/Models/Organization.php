<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    // use SoftDeletes;

    /* =====================
     |  Fillable
     ===================== */

    protected $fillable = [
        // Identidad
        'name',
        'slug',
        'type',              // root | client
        'is_internal',       // true para MarCorp

        // Dueño
        'owner_user_id',

        // Estado
        'status',

        // Contacto
        'phone',
        'email',

        // Branding / White-label
        'theme_key',
        'primary_color',
        'secondary_color',
        'logo_url',
        'white_label',

        // Dominios
        'primary_domain',
        'domains',
        'force_https',

        // Extra
        'metadata',
    ];

    /* =====================
     |  Casts
     ===================== */

    protected $casts = [
        'metadata'     => 'array',
        'phone'        => 'array',
        'domains'      => 'array',

        'is_internal'  => 'boolean',
        'white_label'  => 'boolean',
        'force_https'  => 'boolean',
    ];

    /* =====================
     |  Relaciones
     ===================== */

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'organization_users')
            ->withPivot(['role', 'status', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function subsystems()
    {
        return $this->hasMany(OrganizationSubsystem::class);
    }

    /* =====================
     |  Scopes
     ===================== */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRoot($query)
    {
        return $query->where('type', 'root');
    }

    public function scopeClients($query)
    {
        return $query->where('type', 'client');
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    /* =====================
     |  Helpers de negocio
     ===================== */

    public function isRoot(): bool
    {
        return $this->type === 'root';
    }

    public function isClient(): bool
    {
        return $this->type === 'client';
    }

    public function usesWhiteLabel(): bool
    {
        return (bool) $this->white_label;
    }

    /* =====================
     |  Branding helpers
     ===================== */

    public function branding(): array
    {
        return [
            'theme'           => $this->theme_key ?? 'default',
            'primary_color'   => $this->primary_color ?? '#0F172A', // MarCorp default
            'secondary_color' => $this->secondary_color ?? '#38BDF8',
            'logo_url'        => $this->logo_url ?? asset('branding/marcorp-logo.svg'),
        ];
    }

    /* =====================
     |  Dominio helpers
     ===================== */

    public function ownsDomain(string $domain): bool
    {
        return $this->primary_domain === $domain
            || in_array($domain, $this->domains ?? []);
    }
}
