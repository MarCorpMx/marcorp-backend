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
        'reference_prefix',
        'type',              // root | client
        'is_internal',       // true para MarCorp

        // Dueño
        'owner_user_id',

        // Estado
        'status',

        // Onboarding
        'onboarding_step',
        'onboarding_completed_at',

        // Contacto
        'phone',
        'email',
        'website',

        // Ubicación
        'country',
        'state',
        'city',
        'zip_code',
        'address',

        // Facturación (SAT)
        'legal_name',
        'tax_id',
        'tax_regime',
        'invoice_zip_code',
        'cfdi_email',

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

        // sistema
        'timezone',
        'metadata',
    ];

    /* =====================
     |  Casts
     ===================== */

    protected $casts = [
        'metadata'     => 'array',
        'phone'        => 'array',
        'domains'      => 'array',

        'onboarding_completed_at' => 'datetime',

        'is_internal'  => 'boolean',
        'white_label'  => 'boolean',
        'force_https'  => 'boolean',
    ];


    // Constantes de variables para Onboarding
    // if ($org->onboarding_step === Organization::ONBOARDING_SERVICE_CREATED)
    public const ONBOARDING_EMAIL_PENDING = 'email_pending';
    public const ONBOARDING_BUSINESS_SETUP = 'business_setup';
    public const ONBOARDING_SERVICE_CREATED = 'service_created';
    public const ONBOARDING_AVAILABILITY_SET = 'availability_set';
    public const ONBOARDING_COMPLETED = 'completed';


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

    /*public function subsystems()
    {
        return $this->hasMany(OrganizationSubsystem::class);
    }*/

    // Para lógica interna (login, register, control total)
    public function organizationSubsystems()
    {
        return $this->hasMany(\App\Models\OrganizationSubsystem::class);
    }

    // Para acceso directo a subsystems (FeatureService)
    public function subsystems()
    {
        return $this->belongsToMany(\App\Models\Subsystem::class, 'organization_subsystems')
            ->withPivot([
                'plan_id',
                'status',
                'started_at',
                'expires_at',
                'renews_at',
                'cancelled_at',
                'is_paid',
                'metadata'
            ])
            ->withTimestamps();
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function addons()
    {
        return $this->hasMany(OrganizationAddon::class);
    }

    public function mailSettings()
    {
        return $this->hasMany(OrganizationMailSetting::class);
    }

    public function mailTemplates()
    {
        //return $this->hasMany(OrganizationMailTemplate::class);
        return $this->hasMany(NotificationTemplate::class);
    }

    public function notificationSetting()
    {
        return $this->hasOne(OrganizationNotificationSetting::class);
    }

    public function notificationRules()
    {
        return $this->hasMany(NotificationRule::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function staffMembers()
    {
        return $this->hasMany(StaffMember::class);
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

    public function fullAddress(): string
    {
        return collect([
            $this->address,
            $this->city,
            $this->state,
            $this->zip_code,
            $this->country,
        ])->filter()->implode(', ');
    }

    /* =====================
     |  Dominio helpers
     ===================== */

    public function ownsDomain(string $domain): bool
    {
        return $this->primary_domain === $domain
            || in_array($domain, $this->domains ?? []);
    }

    /* =====================
     |  timezone helpers
     ===================== */
    /*public function getTimezoneAttribute(): string
    {
        return data_get($this->metadata, 'timezone', 'rombiAmerica/Mexico_City');
    }*/

    /* =====================
     | reference_prefix helpers
     ===================== */

    public function setReferencePrefixAttribute($value)
    {
        $this->attributes['reference_prefix'] = strtoupper($value);
    }

    // Onboarding
    public function isOnboardingCompleted(): bool
    {
        return !is_null($this->onboarding_completed_at);
    }

    /*public function advanceOnboarding(string $step): void
    {
        $this->update([
            'onboarding_step' => $step,
            'onboarding_completed_at' => $step === self::ONBOARDING_COMPLETED
                ? now()
                : null
        ]);
    }*/

    public function fiscalData(): array
    {
        return [
            'legal_name'       => $this->legal_name,
            'tax_id'           => $this->tax_id,
            'tax_regime'       => $this->tax_regime,
            'zip_code'         => $this->invoice_zip_code,
            'email'            => $this->cfdi_email,
        ];
    }

    public function advanceOnboarding(string $step): void
    {
        $this->update([
            'onboarding_step' => $step
        ]);
    }
}
